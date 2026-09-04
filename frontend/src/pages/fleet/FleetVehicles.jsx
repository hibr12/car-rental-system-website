import React, { useState, useEffect } from 'react';
import { Car, Loader2, Search, Filter, Eye } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { TableRowSkeleton } from '../../components/common/Skeleton';
import { Modal } from '../../components/common/Modal';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

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
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Fleet Management"
        title="Vehicle Fleet"
        description="Browse, search, and manage all registered vehicles"
        actions={
          <ManagementButton variant="secondary" onClick={fetchVehicles}>
            <Loader2 className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            Refresh
          </ManagementButton>
        }
      />

      {/* Filters */}
      <ManagementCard className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by brand, model, or registration..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="mgmt-input pl-10"
          />
        </div>
        <div className="flex items-center gap-2">
          <Filter className="w-4 h-4 text-[#64748B]" />
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="mgmt-input w-auto"
          >
            <option value="">All Statuses</option>
            <option value="available">Available</option>
            <option value="rented">Rented</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>
      </ManagementCard>

      {/* Vehicles Table */}
      <ManagementCard className="rounded-2xl space-y-6">
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
          <ManagementEmptyState
            icon={Car}
            title="No Vehicles Found"
            description={
              searchQuery || filterStatus ? 'Try adjusting your filters.' : 'No vehicles in the fleet yet.'
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="mgmt-table-head">
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
              <tbody className="divide-y divide-[#E2E8F0]">
                {filteredVehicles.map((vehicle) => (
                  <tr key={vehicle.id} className="mgmt-table-row">
                    <td className="py-4 px-4 font-medium text-[#0F172A]">
                      {vehicle.brand} {vehicle.model}
                    </td>
                    <td className="py-4 px-4 font-mono text-xs text-[#64748B]">{vehicle.registration_number}</td>
                    <td className="py-4 px-4 text-[#334155] capitalize">{vehicle.category?.name || 'N/A'}</td>
                    <td className="py-4 px-4 font-bold text-[#16A34A]">{formatCurrency(vehicle.rental_price_per_day)}</td>
                    <td className="py-4 px-4 text-[#334155]">{vehicle.seating_capacity || vehicle.seats || 'N/A'}</td>
                    <td className="py-4 px-4 text-[#334155] capitalize">{vehicle.transmission}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(vehicle.status)}`}>
                        {formatStatus(vehicle.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <button
                        onClick={() => openDetail(vehicle)}
                        className="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-50 text-[#2563EB] border border-blue-200 hover:bg-blue-100 transition-colors"
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
      </ManagementCard>

      {/* Vehicle Detail Modal */}
      <Modal isOpen={detailOpen} onClose={() => setDetailOpen(false)} title="Vehicle Details">
        {selectedVehicle && (
          <div className="space-y-6">
            <div className="flex items-start justify-between">
              <div>
                <h4 className="text-xl font-bold text-[#0F172A]">
                  {selectedVehicle.brand} {selectedVehicle.model}
                </h4>
                <p className="text-sm text-[#64748B] font-mono">{selectedVehicle.registration_number}</p>
              </div>
              <span className={`px-3 py-1 text-xs font-bold rounded-lg border ${getStatusBadgeStyle(selectedVehicle.status)}`}>
                {formatStatus(selectedVehicle.status)}
              </span>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="bg-[#F8FAFC] rounded-xl p-4 border border-[#E2E8F0]">
                <p className="text-[11px] uppercase font-bold text-[#64748B] mb-1">Daily Rate</p>
                <p className="text-lg font-extrabold text-[#16A34A]">{formatCurrency(selectedVehicle.rental_price_per_day)}</p>
              </div>
              <div className="bg-[#F8FAFC] rounded-xl p-4 border border-[#E2E8F0]">
                <p className="text-[11px] uppercase font-bold text-[#64748B] mb-1">Category</p>
                <p className="text-lg font-extrabold text-[#0F172A] capitalize">{selectedVehicle.category?.name || 'N/A'}</p>
              </div>
              <div className="bg-[#F8FAFC] rounded-xl p-4 border border-[#E2E8F0]">
                <p className="text-[11px] uppercase font-bold text-[#64748B] mb-1">Transmission</p>
                <p className="text-lg font-extrabold text-[#0F172A] capitalize">{selectedVehicle.transmission}</p>
              </div>
              <div className="bg-[#F8FAFC] rounded-xl p-4 border border-[#E2E8F0]">
                <p className="text-[11px] uppercase font-bold text-[#64748B] mb-1">Seating Capacity</p>
                <p className="text-lg font-extrabold text-[#0F172A]">{selectedVehicle.seating_capacity || selectedVehicle.seats || 'N/A'}</p>
              </div>
            </div>

            {selectedVehicle.year && (
              <div className="bg-[#F8FAFC] rounded-xl p-4 border border-[#E2E8F0]">
                <p className="text-[11px] uppercase font-bold text-[#64748B] mb-1">Year</p>
                <p className="text-[#334155]">{selectedVehicle.year}</p>
              </div>
            )}

            {selectedVehicle.description && (
              <div className="bg-[#F8FAFC] rounded-xl p-4 border border-[#E2E8F0]">
                <p className="text-[11px] uppercase font-bold text-[#64748B] mb-2">Description</p>
                <p className="text-sm text-[#334155] leading-relaxed">{selectedVehicle.description}</p>
              </div>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
};

export default FleetVehicles;
