import React from 'react';
import { Search, Filter, RotateCcw } from 'lucide-react';

export const VehicleFilter = ({
  filters,
  onChange,
  onReset,
  categories = [],
  branches = [],
  showDates = true,
  sortOptions = [
    { label: 'Newest First', value: 'newest' },
    { label: 'Price: Low to High', value: 'price_asc' },
    { label: 'Price: High to Low', value: 'price_desc' },
    { label: 'Year: Newest', value: 'year_desc' },
    { label: 'Year: Oldest', value: 'year_asc' },
  ],
}) => {
  const handleInputChange = (field, value) => {
    onChange({ ...filters, [field]: value, page: 1 });
  };

  return (
    <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-6 shadow-xl transition-colors duration-200">
      <div className="flex items-center justify-between pb-4 border-b border-theme">
        <div className="flex items-center gap-2 text-theme-primary font-semibold">
          <Filter className="w-5 h-5 text-blue-400" />
          <span>Search & Filter Vehicles</span>
        </div>
        <button
          onClick={onReset}
          className="flex items-center gap-1.5 text-xs text-theme-muted hover:text-theme-primary transition-colors py-1 px-2.5 rounded-lg border border-theme hover:border-theme-hover bg-theme-input"
        >
          <RotateCcw className="w-3.5 h-3.5" />
          <span>Reset Filters</span>
        </button>
      </div>

      {/* Branch Selection */}
      {branches.length > 0 && (
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-2">Pickup Branch</label>
          <select
            value={filters.branch_id || ''}
            onChange={(e) => handleInputChange('branch_id', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl px-3.5 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          >
            <option value="">All Branches</option>
            {branches.map((b) => (
              <option key={b.id} value={b.id}>
                {b.name}
                {typeof b.available_vehicles_count === 'number'
                  ? ` (${b.available_vehicles_count} available)`
                  : ''}
              </option>
            ))}
          </select>
        </div>
      )}

      {/* Rental Dates */}
      {showDates && (
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Pickup Date</label>
            <input
              type="date"
              value={filters.pickup_date || ''}
              onChange={(e) => handleInputChange('pickup_date', e.target.value)}
              min={new Date().toISOString().split('T')[0]}
              className="w-full bg-theme-input border border-theme rounded-xl px-3 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
            />
          </div>
          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Return Date</label>
            <input
              type="date"
              value={filters.return_date || ''}
              onChange={(e) => handleInputChange('return_date', e.target.value)}
              min={filters.pickup_date || new Date().toISOString().split('T')[0]}
              className="w-full bg-theme-input border border-theme rounded-xl px-3 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
            />
          </div>
        </div>
      )}

      {/* Search Input */}
      <div>
        <label className="block text-xs font-semibold text-theme-secondary mb-2">Search Vehicles</label>
        <div className="relative">
          <Search className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by brand, model, description..."
            value={filters.search || ''}
            onChange={(e) => handleInputChange('search', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
          />
        </div>
      </div>

      {/* Categories */}
      {categories.length > 0 && (
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-2">Vehicle Category</label>
          <select
            value={filters.category || ''}
            onChange={(e) => handleInputChange('category', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl px-3.5 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          >
            <option value="">All Categories</option>
            {categories.map((cat) => (
              <option key={cat.id} value={cat.slug}>
                {cat.name}
              </option>
            ))}
          </select>
        </div>
      )}

      {/* Price Range */}
      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Min Price ($)</label>
          <input
            type="number"
            placeholder="0"
            min="0"
            value={filters.min_price || ''}
            onChange={(e) => handleInputChange('min_price', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl px-3 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          />
        </div>
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Max Price ($)</label>
          <input
            type="number"
            placeholder="1000+"
            min="0"
            value={filters.max_price || ''}
            onChange={(e) => handleInputChange('max_price', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl px-3 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          />
        </div>
      </div>

      {/* Fuel Type & Transmission */}
      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Fuel Type</label>
          <select
            value={filters.fuel_type || ''}
            onChange={(e) => handleInputChange('fuel_type', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl px-3 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          >
            <option value="">Any Fuel</option>
            <option value="petrol">Petrol</option>
            <option value="diesel">Diesel</option>
            <option value="electric">Electric</option>
            <option value="hybrid">Hybrid</option>
          </select>
        </div>
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Transmission</label>
          <select
            value={filters.transmission || ''}
            onChange={(e) => handleInputChange('transmission', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl px-3 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          >
            <option value="">Any Transmission</option>
            <option value="automatic">Automatic</option>
            <option value="manual">Manual</option>
          </select>
        </div>
      </div>

      {/* Status & Sort */}
      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Availability</label>
          <select
            value={filters.status || ''}
            onChange={(e) => handleInputChange('status', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl px-3 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          >
            <option value="">All Statuses</option>
            <option value="available">Available Only</option>
            <option value="rented">Rented</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>
        <div>
          <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Sort By</label>
          <select
            value={filters.sort || 'newest'}
            onChange={(e) => handleInputChange('sort', e.target.value)}
            className="w-full bg-theme-input border border-theme rounded-xl px-3 py-2 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors"
          >
            {sortOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>
      </div>
    </div>
  );
};

export default VehicleFilter;
