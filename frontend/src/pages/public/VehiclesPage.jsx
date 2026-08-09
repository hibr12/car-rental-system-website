import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Car, AlertCircle, Filter } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import categoryApi from '../../api/categoryApi';
import VehicleCard from '../../components/vehicles/VehicleCard';
import VehicleFilter from '../../components/vehicles/VehicleFilter';
import { VehicleCardSkeleton } from '../../components/common/Skeleton';
import Pagination from '../../components/common/Pagination';

export const VehiclesPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();

  const [vehicles, setVehicles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Extract filter parameters from URL query params
  const filters = {
    search: searchParams.get('search') || '',
    category: searchParams.get('category') || '',
    min_price: searchParams.get('min_price') || '',
    max_price: searchParams.get('max_price') || '',
    fuel_type: searchParams.get('fuel_type') || '',
    transmission: searchParams.get('transmission') || '',
    status: searchParams.get('status') || '',
    sort: searchParams.get('sort') || 'newest',
    page: parseInt(searchParams.get('page') || '1', 10),
  };

  // Fetch Categories
  useEffect(() => {
    categoryApi
      .getAll()
      .then((res) => setCategories(res.data || []))
      .catch((err) => console.error('Failed to load categories:', err));
  }, []);

  // Fetch Vehicles
  const fetchVehicles = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const params = {};
      if (filters.search) params.search = filters.search;
      if (filters.category) params.category = filters.category;
      if (filters.min_price) params.min_price = filters.min_price;
      if (filters.max_price) params.max_price = filters.max_price;
      if (filters.fuel_type) params.fuel_type = filters.fuel_type;
      if (filters.transmission) params.transmission = filters.transmission;
      if (filters.status) params.status = filters.status;
      if (filters.sort) params.sort = filters.sort;
      params.page = filters.page;
      params.per_page = 9;

      const res = await vehicleApi.getAll(params);
      setVehicles(res.data || []);
      if (res.meta) {
        setMeta(res.meta);
      }
    } catch (err) {
      setError(err.message || 'Failed to retrieve vehicle listings.');
    } finally {
      setLoading(false);
    }
  }, [searchParams]);

  useEffect(() => {
    fetchVehicles();
  }, [fetchVehicles]);

  const handleFilterChange = (newFilters) => {
    const params = new URLSearchParams();
    Object.entries(newFilters).forEach(([key, val]) => {
      if (val !== undefined && val !== null && val !== '') {
        params.set(key, val);
      }
    });
    setSearchParams(params);
  };

  const handleResetFilters = () => {
    setSearchParams({});
  };

  const handlePageChange = (newPage) => {
    handleFilterChange({ ...filters, page: newPage });
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
      {/* Header */}
      <div className="space-y-2 border-b border-slate-800 pb-6">
        <span className="text-xs font-extrabold uppercase tracking-wider text-blue-400">
          Our Fleet Catalog
        </span>
        <h1 className="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">
          Browse & Reserve Vehicles
        </h1>
        <p className="text-sm text-slate-400">
          Explore our available premium vehicles. Filter by category, price, fuel, and transmission.
        </p>
      </div>

      {/* Main Grid: Filters + Vehicles */}
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
        {/* Left Sidebar Filter */}
        <div className="lg:col-span-1">
          <VehicleFilter
            filters={filters}
            onChange={handleFilterChange}
            onReset={handleResetFilters}
            categories={categories}
          />
        </div>

        {/* Right Vehicles Catalog */}
        <div className="lg:col-span-3 space-y-6">
          {/* Results Stats */}
          <div className="flex items-center justify-between text-xs text-slate-400 bg-slate-900 border border-slate-800 px-4 py-3 rounded-xl">
            <span>
              Showing <strong className="text-white">{vehicles.length}</strong> of{' '}
              <strong className="text-white">{meta.total || vehicles.length}</strong> vehicles
            </span>
            {filters.category && (
              <span className="bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2.5 py-0.5 rounded-full font-semibold capitalize">
                Category: {filters.category}
              </span>
            )}
          </div>

          {/* Loading Grid */}
          {loading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <VehicleCardSkeleton />
              <VehicleCardSkeleton />
              <VehicleCardSkeleton />
              <VehicleCardSkeleton />
              <VehicleCardSkeleton />
              <VehicleCardSkeleton />
            </div>
          ) : error ? (
            /* Error State */
            <div className="bg-slate-900 border border-rose-500/30 rounded-3xl p-12 text-center space-y-4">
              <AlertCircle className="w-12 h-12 text-rose-400 mx-auto" />
              <h3 className="text-lg font-bold text-white">Error Loading Catalog</h3>
              <p className="text-xs text-slate-400 max-w-md mx-auto">{error}</p>
              <button
                onClick={fetchVehicles}
                className="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-xs shadow-md shadow-blue-600/20"
              >
                Try Again
              </button>
            </div>
          ) : vehicles.length === 0 ? (
            /* Empty State */
            <div className="bg-slate-900 border border-slate-800 rounded-3xl p-12 text-center space-y-4">
              <Car className="w-12 h-12 text-slate-600 mx-auto" />
              <h3 className="text-lg font-bold text-white">No Vehicles Match Your Criteria</h3>
              <p className="text-xs text-slate-400 max-w-sm mx-auto">
                Try adjusting your search query or clearing selected category and price filters.
              </p>
              <button
                onClick={handleResetFilters}
                className="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-xs"
              >
                Clear All Filters
              </button>
            </div>
          ) : (
            /* Vehicles Cards Grid */
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {vehicles.map((vehicle) => (
                <VehicleCard key={vehicle.id} vehicle={vehicle} />
              ))}
            </div>
          )}

          {/* Pagination */}
          {!loading && meta.last_page > 1 && (
            <Pagination
              currentPage={meta.current_page}
              lastPage={meta.last_page}
              total={meta.total}
              onPageChange={handlePageChange}
            />
          )}
        </div>
      </div>
    </div>
  );
};

export default VehiclesPage;
