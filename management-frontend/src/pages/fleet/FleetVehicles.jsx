import React, { useState, useEffect } from 'react';
import { Car, Loader2, Search, Filter, Eye, X } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { TableRowSkeleton } from '../../components/common/Skeleton';
import { Modal } from '../../components/common/Modal';

export const FleetVehicles = () => {
  const [vehicles, setVehicles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [selectedVehicle, setSelectedVehicle] = useState(null);
  const [detailOpen, setDetailOpen] = useState(false);

  const fetchVehicles = () => {
    setLoading(true);
    vehicleApi
      .getAll({ per_page: 100 })
      .then((res) => {
        setVehicles(res.data || []);
      })
      .catch((err) => {
        console.error('Failed to load vehicles:', err);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchVehicles();
  }, []);

  const filteredVehicles = vehicles.filter((v) => {
    const matchesStatus = !filterStatus || v.status === filterStatus;
    const matchesSearch =
      !searchQuery ||
      v.brand?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      v.model?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      v.registration_number?.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesStatus && matchesSearch;
  });

  const openDetail = (vehicle) => {
    setSelectedVehicle(vehicle);
    setDetailOpen(true);
  };

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-theme pb-6">
        <div>
          <span className="text-xs uppercase font-extrabold tracking-wider text-indigo-400">
            Fleet Management
          </span>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Vehicle Fleet</h1>
          <p className="text-sm text-theme-muted mt-1">Browse, search, and manage all registered vehicles</p>
        </div>
        <button
          onClick={fetchVehicles}
          className="px-4 py-2.5 rounded-xl bg-theme-secondary border border-theme hover:bg-theme-hover text-theme-secondary font-semibold text-xs flex items-center gap-2 transition-colors"
        >
          <Loader2 className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
          Refresh
        </button>
      </div>

      {/* Filters */}
      <div className="bg-theme-card border border-theme rounded-2xl p-4 flex flex-col sm:flex-row gap-4 transition-colors duration-200">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by brand, model, or registration..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
          />
        </div>
        <div className="flex items-center gap-2">
          <Filter className="w-4 h-4 text-theme-muted" />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="bg-theme-input border border-theme rounded-xl px-3 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          >
            <option value="">All Statuses</option>
            <option value="available">Available</option>
            <option value="rented">Rented</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>
      </div>

      {/* Vehicles Table */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        {loading ? (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <tbody>
                {[1, 2, 3, 4, 5].map((i) => (
                  <TableRowSkeleton key={i} cols={6} />
                ))}
              </tbody>
            </table>
          </div>
        ) : filteredVehicles.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <Car className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Vehicles Found</p>
            <p className="text-xs text-theme-muted">
              {searchQuery || filterStatus ? 'Try adjusting your filters.' : 'No vehicles in the fleet yet.'}
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-theme-secondary">
              <thead className="text-xs uppercase bg-theme-hover text-theme-muted border-b border-theme">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Registration</th>
                  <th className="py-3.5 px-4 font-semibold">Category</th>
                  <th className="py-3.5 px-4 font-semibold">Daily Rate</th>
                  <th className="py-3.5 px-4 font-semibold">Seats</th>
                  <th className="py-3.5 px-4 font-semibold">Transmission</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Details</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-theme">
                {filteredVehicles.map((vehicle) => (
                  <tr key={vehicle.id} className="hover:bg-theme-hover transition-colors">
                    <td className="py-4 px-4 font-medium text-theme-primary">
                      {vehicle.brand} {vehicle.model}
                    </td>
                    <td className="py-4 px-4 font-mono text-xs text-theme-muted">{vehicle.registration_number}</td>
                    <td className="py-4 px-4 text-theme-secondary capitalize">{vehicle.category?.name || 'N/A'}</td>
                    <td className="py-4 px-4 font-bold text-emerald-400">{formatCurrency(vehicle.rental_price_per_day)}</td>
                    <td className="py-4 px-4 text-theme-secondary">{vehicle.seating_capacity || vehicle.seats || 'N/A'}</td>
                    <td className="py-4 px-4 text-theme-secondary capitalize">{vehicle.transmission}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(vehicle.status)}`}>
                        {formatStatus(vehicle.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <button
                        onClick={() => openDetail(vehicle)}
                        className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition-colors"
                      >
                        <Eye className="w-3 h-3 inline mr-1" />
                        View
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Vehicle Detail Modal */}
      <Modal isOpen={detailOpen} onClose={() => setDetailOpen(false)} title="Vehicle Details">
        {selectedVehicle && (
          <div className="space-y-6">
            <div className="flex items-start justify-between">
              <div>
                <h4 className="text-xl font-bold text-theme-primary">
                  {selectedVehicle.brand} {selectedVehicle.model}
                </h4>
                <p className="text-sm text-theme-muted font-mono">{selectedVehicle.registration_number}</p>
              </div>
              <span className={`px-3 py-1 text-xs font-bold rounded-lg border ${getStatusBadgeStyle(selectedVehicle.status)}`}>
                {formatStatus(selectedVehicle.status)}
              </span>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="bg-theme-input rounded-xl p-4 border border-theme">
                <p className="text-[11px] uppercase font-bold text-theme-muted mb-1">Daily Rate</p>
                <p className="text-lg font-extrabold text-emerald-400">{formatCurrency(selectedVehicle.rental_price_per_day)}</p>
              </div>
              <div className="bg-theme-input rounded-xl p-4 border border-theme">
                <p className="text-[11px] uppercase font-bold text-theme-muted mb-1">Category</p>
                <p className="text-lg font-extrabold text-theme-primary capitalize">{selectedVehicle.category?.name || 'N/A'}</p>
              </div>
              <div className="bg-theme-input rounded-xl p-4 border border-theme">
                <p className="text-[11px] uppercase font-bold text-theme-muted mb-1">Transmission</p>
                <p className="text-lg font-extrabold text-theme-primary capitalize">{selectedVehicle.transmission}</p>
              </div>
              <div className="bg-theme-input rounded-xl p-4 border border-theme">
                <p className="text-[11px] uppercase font-bold text-theme-muted mb-1">Seating Capacity</p>
                <p className="text-lg font-extrabold text-theme-primary">{selectedVehicle.seating_capacity || selectedVehicle.seats || 'N/A'}</p>
              </div>
            </div>

            {selectedVehicle.year && (
              <div className="bg-theme-input rounded-xl p-4 border border-theme">
                <p className="text-[11px] uppercase font-bold text-theme-muted mb-1">Year</p>
                <p className="text-theme-secondary">{selectedVehicle.year}</p>
              </div>
            )}

            {selectedVehicle.description && (
              <div className="bg-theme-input rounded-xl p-4 border border-theme">
                <p className="text-[11px] uppercase font-bold text-theme-muted mb-2">Description</p>
                <p className="text-sm text-theme-secondary leading-relaxed">{selectedVehicle.description}</p>
              </div>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
};

export default FleetVehicles;
