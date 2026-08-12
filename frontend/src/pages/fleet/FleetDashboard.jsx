import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Car, Wrench, Plus, AlertTriangle, CheckCircle2, ArrowRightLeft,
  Loader2, TrendingUp,
} from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import maintenanceApi from '../../api/maintenanceApi';
import fleetApi from '../../api/fleetApi';
import { formatCurrency, formatStatus, formatDate, getStatusBadgeStyle } from '../../utils/formatters';
import { StatCardSkeleton } from '../../components/common/Skeleton';
import { ManagementCard } from '../../components/management/ManagementUI';

const StatCard = ({ label, value, icon: Icon, colorClass, bgClass }) => (
  <ManagementCard className="space-y-3">
    <div className="flex items-center justify-between">
      <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">{label}</span>
      <div className={`w-10 h-10 rounded-xl ${bgClass} ${colorClass} flex items-center justify-center border`}>
        <Icon className="w-5 h-5" />
      </div>
    </div>
    <p className={`text-3xl font-extrabold ${colorClass}`}>{value}</p>
  </ManagementCard>
);

export const FleetDashboard = () => {
  const [stats, setStats] = useState(null);
  const [vehicles, setVehicles] = useState([]);
  const [maintenanceRecords, setMaintenanceRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    Promise.all([
      fleetApi.getDashboard(),
      vehicleApi.getAll({ per_page: 8 }),
      maintenanceApi.getAll({ per_page: 5 }),
    ])
      .then(([statsRes, vehRes, maintRes]) => {
        setStats(statsRes.data || null);
        setVehicles(vehRes.data || []);
        setMaintenanceRecords(maintRes.data || []);
      })
      .catch((err) => setError(err.message || 'Unable to load fleet dashboard.'))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="mgmt-page space-y-8">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
            <StatCardSkeleton key={i} />
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="mgmt-page">
        <ManagementCard className="text-center py-12 space-y-3">
          <AlertTriangle className="w-10 h-10 text-[#DC2626] mx-auto" />
          <p className="text-sm text-[#334155]">{error}</p>
        </ManagementCard>
      </div>
    );
  }

  const s = stats || {};

  return (
    <div className="mgmt-page space-y-8">
      <ManagementCard className="rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-2">
          <span className="text-xs uppercase font-semibold tracking-wider text-[#64748B]">Fleet Overview</span>
          <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">Fleet Manager Workstation</h1>
          <p className="text-sm text-[#64748B] max-w-xl">
            Monitor vehicle inventory, availability, maintenance, and transfers across the company fleet.
          </p>
          {s.utilization_pct != null && (
            <p className="text-sm font-semibold text-[#2563EB] flex items-center gap-1.5">
              <TrendingUp className="w-4 h-4" />
              Fleet utilization: {s.utilization_pct}%
            </p>
          )}
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <Link to="/fleet/vehicles" className="px-5 py-3 rounded-xl bg-[#2563EB] hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-2">
            <Plus className="w-4 h-4" />
            Manage Vehicles
          </Link>
          <Link to="/fleet/maintenance" className="px-5 py-3 rounded-xl bg-white border border-[#E2E8F0] hover:bg-[#F8FAFC] text-[#334155] font-bold text-xs flex items-center gap-2">
            <Wrench className="w-4 h-4 text-[#2563EB]" />
            Maintenance
          </Link>
        </div>
      </ManagementCard>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard label="Total Vehicles" value={s.total_vehicles ?? 0} icon={Car} colorClass="text-[#0F172A]" bgClass="bg-blue-50 border-blue-100" />
        <StatCard label="Available" value={s.available ?? 0} icon={CheckCircle2} colorClass="text-[#16A34A]" bgClass="bg-green-50 border-green-100" />
        <StatCard label="Reserved" value={s.reserved ?? 0} icon={Car} colorClass="text-[#2563EB]" bgClass="bg-blue-50 border-blue-100" />
        <StatCard label="Active Rental" value={s.active_rental ?? 0} icon={Car} colorClass="text-[#DC2626]" bgClass="bg-red-50 border-red-100" />
        <StatCard label="Under Maintenance" value={s.maintenance ?? 0} icon={Wrench} colorClass="text-[#F59E0B]" bgClass="bg-amber-50 border-amber-100" />
        <StatCard label="Out of Service" value={s.out_of_service ?? 0} icon={AlertTriangle} colorClass="text-[#64748B]" bgClass="bg-slate-100 border-slate-200" />
        <StatCard label="Transfer Pending" value={s.transfer_pending ?? 0} icon={ArrowRightLeft} colorClass="text-[#7C3AED]" bgClass="bg-purple-50 border-purple-100" />
        <StatCard label="Inspection Required" value={s.inspection_required ?? 0} icon={CheckCircle2} colorClass="text-[#F59E0B]" bgClass="bg-amber-50 border-amber-100" />
        <StatCard label="Documents Expiring" value={s.documents_expiring ?? 0} icon={AlertTriangle} colorClass="text-[#DC2626]" bgClass="bg-red-50 border-red-100" />
        <StatCard label="Pending Inspections" value={s.pending_inspections ?? 0} icon={Wrench} colorClass="text-[#2563EB]" bgClass="bg-blue-50 border-blue-100" />
      </div>

      <ManagementCard className="rounded-2xl space-y-6">
        <div className="flex justify-between items-center pb-4 border-b border-[#E2E8F0]">
          <div>
            <h3 className="text-lg font-bold text-[#0F172A]">Fleet Inventory</h3>
            <p className="text-xs text-[#64748B]">Recent vehicles across all branches</p>
          </div>
          <Link to="/fleet/vehicles" className="text-xs text-[#2563EB] hover:underline">View All</Link>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-[#334155]">
            <thead className="mgmt-table-head">
              <tr>
                <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                <th className="py-3.5 px-4 font-semibold">Branch</th>
                <th className="py-3.5 px-4 font-semibold">Registration</th>
                <th className="py-3.5 px-4 font-semibold">Daily Rate</th>
                <th className="py-3.5 px-4 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#E2E8F0]">
              {vehicles.map((v) => (
                <tr key={v.id} className="mgmt-table-row">
                  <td className="py-4 px-4 font-medium text-[#0F172A]">{v.brand} {v.model}</td>
                  <td className="py-4 px-4 text-xs">{v.branch?.name || '—'}</td>
                  <td className="py-4 px-4 font-mono text-xs text-[#64748B]">{v.registration_number}</td>
                  <td className="py-4 px-4 font-bold text-[#16A34A]">{formatCurrency(v.rental_price_per_day)}</td>
                  <td className="py-4 px-4">
                    <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(v.status)}`}>
                      {formatStatus(v.status)}
                    </span>
                  </td>
                </tr>
              ))}
              {vehicles.length === 0 && (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-[#64748B] text-sm">No vehicles found.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </ManagementCard>

      <ManagementCard className="rounded-2xl space-y-6">
        <div className="flex justify-between items-center pb-4 border-b border-[#E2E8F0]">
          <div>
            <h3 className="text-lg font-bold text-[#0F172A]">Recent Maintenance</h3>
            <p className="text-xs text-[#64748B]">Latest fleet maintenance activity</p>
          </div>
          <Link to="/fleet/maintenance" className="text-xs text-[#2563EB] hover:underline">View All</Link>
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
              {maintenanceRecords.map((m) => (
                <tr key={m.id} className="mgmt-table-row">
                  <td className="py-4 px-4 font-medium text-[#0F172A]">
                    {m.vehicle ? `${m.vehicle.brand} ${m.vehicle.model}` : `Vehicle #${m.vehicle_id}`}
                  </td>
                  <td className="py-4 px-4 capitalize">{m.maintenance_type || m.type || 'General'}</td>
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
                  <td colSpan="5" className="py-12 text-center text-[#64748B] text-sm">No maintenance records yet.</td>
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
