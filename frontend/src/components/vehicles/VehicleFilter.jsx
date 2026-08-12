import React from 'react';
import { Search, Filter, RotateCcw } from 'lucide-react';

const INPUT_CLS =
  'w-full bg-[#1e293b] border border-[#334155] rounded-xl px-3 py-2.5 text-sm text-white placeholder-[#64748B] focus:outline-none focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/30 transition-colors';

const LABEL_CLS = 'block text-xs font-semibold text-[#CBD5E1] mb-1.5';

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
    <div className="bg-[#0F172A] border border-[#1e293b] rounded-2xl shadow-xl flex flex-col max-h-[min(720px,calc(100vh-7rem))] lg:max-h-[calc(100vh-7rem)] overflow-hidden">
      {/* Header — matches admin sidebar tone */}
      <div className="flex items-center justify-between px-5 py-4 border-b border-[#1e293b] shrink-0">
        <div className="flex items-center gap-2.5 text-white font-semibold">
          <div className="w-8 h-8 rounded-lg bg-[#2563EB] flex items-center justify-center shrink-0">
            <Filter className="w-4 h-4 text-white" />
          </div>
          <span className="text-sm leading-tight">Search & Filter Vehicles</span>
        </div>
        <button
          type="button"
          onClick={onReset}
          className="flex items-center gap-1.5 text-xs text-[#CBD5E1] hover:text-white transition-colors py-1.5 px-2.5 rounded-lg border border-[#334155] hover:bg-[#1e293b] hover:border-[#475569]"
        >
          <RotateCcw className="w-3.5 h-3.5" />
          <span>Reset</span>
        </button>
      </div>

      {/* Scrollable filter body */}
      <div className="overflow-y-auto overscroll-y-contain px-5 py-5 space-y-5 flex-1 min-h-0">
        {branches.length > 0 && (
          <div>
            <label className={LABEL_CLS}>Pickup Branch</label>
            <select
              value={filters.branch_id || ''}
              onChange={(e) => handleInputChange('branch_id', e.target.value)}
              className={INPUT_CLS}
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

        {showDates && (
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className={LABEL_CLS}>Pickup Date</label>
              <input
                type="date"
                value={filters.pickup_date || ''}
                onChange={(e) => handleInputChange('pickup_date', e.target.value)}
                min={new Date().toISOString().split('T')[0]}
                className={INPUT_CLS}
              />
            </div>
            <div>
              <label className={LABEL_CLS}>Return Date</label>
              <input
                type="date"
                value={filters.return_date || ''}
                onChange={(e) => handleInputChange('return_date', e.target.value)}
                min={filters.pickup_date || new Date().toISOString().split('T')[0]}
                className={INPUT_CLS}
              />
            </div>
          </div>
        )}

        <div>
          <label className={LABEL_CLS}>Search Vehicles</label>
          <div className="relative">
            <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              type="text"
              placeholder="Brand, model, description..."
              value={filters.search || ''}
              onChange={(e) => handleInputChange('search', e.target.value)}
              className={`${INPUT_CLS} pl-10`}
            />
          </div>
        </div>

        {categories.length > 0 && (
          <div>
            <label className={LABEL_CLS}>Vehicle Category</label>
            <select
              value={filters.category || ''}
              onChange={(e) => handleInputChange('category', e.target.value)}
              className={INPUT_CLS}
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

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className={LABEL_CLS}>Min Price ($)</label>
            <input
              type="number"
              placeholder="0"
              min="0"
              value={filters.min_price || ''}
              onChange={(e) => handleInputChange('min_price', e.target.value)}
              className={INPUT_CLS}
            />
          </div>
          <div>
            <label className={LABEL_CLS}>Max Price ($)</label>
            <input
              type="number"
              placeholder="1000+"
              min="0"
              value={filters.max_price || ''}
              onChange={(e) => handleInputChange('max_price', e.target.value)}
              className={INPUT_CLS}
            />
          </div>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className={LABEL_CLS}>Fuel Type</label>
            <select
              value={filters.fuel_type || ''}
              onChange={(e) => handleInputChange('fuel_type', e.target.value)}
              className={INPUT_CLS}
            >
              <option value="">Any Fuel</option>
              <option value="petrol">Petrol</option>
              <option value="diesel">Diesel</option>
              <option value="electric">Electric</option>
              <option value="hybrid">Hybrid</option>
            </select>
          </div>
          <div>
            <label className={LABEL_CLS}>Transmission</label>
            <select
              value={filters.transmission || ''}
              onChange={(e) => handleInputChange('transmission', e.target.value)}
              className={INPUT_CLS}
            >
              <option value="">Any Transmission</option>
              <option value="automatic">Automatic</option>
              <option value="manual">Manual</option>
            </select>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className={LABEL_CLS}>Availability</label>
            <select
              value={filters.status || ''}
              onChange={(e) => handleInputChange('status', e.target.value)}
              className={INPUT_CLS}
            >
              <option value="">All Statuses</option>
              <option value="available">Available Only</option>
              <option value="rented">Rented</option>
              <option value="maintenance">Maintenance</option>
            </select>
          </div>
          <div>
            <label className={LABEL_CLS}>Sort By</label>
            <select
              value={filters.sort || 'newest'}
              onChange={(e) => handleInputChange('sort', e.target.value)}
              className={INPUT_CLS}
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
    </div>
  );
};

export default VehicleFilter;
