import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Car, Wrench, Plus, AlertTriangle, CheckCircle2 } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import maintenanceApi from '../../api/maintenanceApi';
import { formatCurrency, formatStatus, formatDate, getStatusBadgeStyle } from '../../utils/formatters';
import { StatCardSkeleton } from '../../components/common/Skeleton';
import { ManagementCard } from '../../components/management/ManagementUI';

export const FleetDashboard = () => {
  const [vehicles, setVehicles] = useState([]);
  const [maintenanceRecords, setMaintenanceRecords] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([vehicleApi.getAll({ per_page: 50 }), maintenanceApi.getAll({ per_page: 10 })])
      .then(([vehRes, maintRes]) => {
        setVehicles(vehRes.data || []);
        setMaintenanceRecords(maintRes.data || []);
      })
      .catch((err) => console.error('Failed to load fleet data:', err))
      .finally(() => setLoading(false));
  }, []);

  const totalCount = vehicles.length;
  const availableCount = vehicles.filter((v) => v.status === 'available').length;
  const maintenanceCount = vehicles.filter((v) => v.status === 'maintenance').length;
  const rentedCount = vehicles.filter((v) => v.status === 'rented').length;

  if (loading) {
    return (
      <div className="mgmt-page space-y-8">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {[1, 2, 3, 4].map((i) => (
            <StatCardSkeleton key={i} />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="mgmt-page space-y-8">
      {/* Header */}
      <ManagementCard className="rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-2">
          <span className="text-xs uppercase font-semibold tracking-wider text-[#64748B]">
            Fleet Operations Control
          </span>
          <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">Fleet Manager Workstation</h1>
          <p className="text-sm text-[#64748B] max-w-xl">
            Monitor vehicle availability, schedule preventative maintenance, and register new fleet additions.
          </p>
        </div>
        <div className="flex items-center gap-3">
          <Link
            to="/fleet/vehicles"
            className="px-5 py-3 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            <span>Add Vehicle</span>
          </Link>
          <Link
            to="/fleet/maintenance"
            className="px-5 py-3 rounded-xl bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] text-[#334155] font-bold text-xs flex items-center gap-2 transition-colors"
          >
            <Wrench className="w-4 h-4 text-[#2563EB]" />
            <span>Schedule Maintenance</span>
          </Link>
        </div>
      </ManagementCard>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <ManagementCard className="space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Total Vehicles</span>
            <div className="w-10 h-10 rounded-xl bg-blue-50 text-[#2563EB] flex items-center justify-center border border-blue-100">
              <Car className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-[#0F172A]">{totalCount}</p>
        </ManagementCard>

        <ManagementCard className="space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Available</span>
            <div className="w-10 h-10 rounded-xl bg-green-50 text-[#16A34A] flex items-center justify-center border border-green-100">
              <CheckCircle2 className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-[#16A34A]">{availableCount}</p>
        </ManagementCard>

        <ManagementCard className="space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Under Maintenance</span>
            <div className="w-10 h-10 rounded-xl bg-amber-50 text-[#F59E0B] flex items-center justify-center border border-amber-100">
              <Wrench className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-[#F59E0B]">{maintenanceCount}</p>
        </ManagementCard>

        <ManagementCard className="space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Rented Out</span>
            <div className="w-10 h-10 rounded-xl bg-red-50 text-[#DC2626] flex items-center justify-center border border-red-100">
              <AlertTriangle className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-[#DC2626]">{rentedCount}</p>
        </ManagementCard>
      </div>

      {/* Vehicles Table */}
      <ManagementCard className="rounded-2xl space-y-6">
        <div className="flex justify-between items-center pb-4 border-b border-[#E2E8F0]">
          <div>
            <h3 className="text-lg font-bold text-[#0F172A]">Active Fleet Inventory</h3>
            <p className="text-xs text-[#64748B]">All registered vehicles and their current status</p>
          </div>
          <Link to="/fleet/vehicles" className="text-xs text-[#2563EB] hover:underline">
            Manage Fleet
          </Link>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-[#334155]">
            <thead className="mgmt-table-head">
              <tr>
                <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                <th className="py-3.5 px-4 font-semibold">Registration</th>
                <th className="py-3.5 px-4 font-semibold">Category</th>
                <th className="py-3.5 px-4 font-semibold">Daily Rate</th>
                <th className="py-3.5 px-4 font-semibold">Transmission</th>
                <th className="py-3.5 px-4 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#E2E8F0]">
              {vehicles.slice(0, 8).map((v) => (
                <tr key={v.id} className="mgmt-table-row">
                  <td className="py-4 px-4 font-medium text-[#0F172A]">{v.brand} {v.model}</td>
                  <td className="py-4 px-4 font-mono text-xs text-[#64748B]">{v.registration_number}</td>
                  <td className="py-4 px-4 text-[#334155] capitalize">{v.category?.name || v.category_id || 'N/A'}</td>
                  <td className="py-4 px-4 font-bold text-[#16A34A]">{formatCurrency(v.rental_price_per_day)}</td>
                  <td className="py-4 px-4 text-[#334155] capitalize">{v.transmission}</td>
                  <td className="py-4 px-4">
                    <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(v.status)}`}>
                      {formatStatus(v.status)}
                    </span>
                  </td>
                </tr>
              ))}
              {vehicles.length === 0 && (
                <tr>
                  <td colSpan="6" className="py-12 text-center text-[#64748B] text-sm">
                    No vehicles found in the fleet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </ManagementCard>

      {/* Recent Maintenance Records */}
      <ManagementCard className="rounded-2xl space-y-6">
        <div className="flex justify-between items-center pb-4 border-b border-[#E2E8F0]">
          <div>
            <h3 className="text-lg font-bold text-[#0F172A]">Recent Maintenance</h3>
            <p className="text-xs text-[#64748B]">Latest scheduled and completed maintenance activities</p>
          </div>
          <Link to="/fleet/maintenance" className="text-xs text-[#2563EB] hover:underline">
            View All
          </Link>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-[#334155]">
            <thead className="mgmt-table-head">
              <tr>
                <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                <th className="py-3.5 px-4 font-semibold">Type</th>
                <th className="py-3.5 px-4 font-semibold">Scheduled</th>
                <th className="py-3.5 px-4 font-semibold">Cost</th>
                <th className="py-3.5 px-4 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#E2E8F0]">
              {maintenanceRecords.slice(0, 5).map((m) => (
                <tr key={m.id} className="mgmt-table-row">
                  <td className="py-4 px-4 font-medium text-[#0F172A]">
                    {m.vehicle ? `${m.vehicle.brand} ${m.vehicle.model}` : `Vehicle #${m.vehicle_id}`}
                  </td>
                  <td className="py-4 px-4 text-[#334155] capitalize">{m.maintenance_type || m.type || 'General'}</td>
                  <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(m.scheduled_date || m.created_at)}</td>
                  <td className="py-4 px-4 font-bold text-[#16A34A]">{formatCurrency(m.cost || 0)}</td>
                  <td className="py-4 px-4">
                    <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(m.status)}`}>
                      {formatStatus(m.status)}
                    </span>
                  </td>
                </tr>
              ))}
              {maintenanceRecords.length === 0 && (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-[#64748B] text-sm">
                    No maintenance records found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </ManagementCard>
    </div>
  );
};

export default FleetDashboard;
