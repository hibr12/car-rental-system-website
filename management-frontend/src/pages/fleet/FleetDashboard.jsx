import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Car, Wrench, Plus, Loader2, Clock, AlertTriangle, CheckCircle2 } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import maintenanceApi from '../../api/maintenanceApi';
import { formatCurrency, formatStatus, formatDate, getStatusBadgeStyle } from '../../utils/formatters';
import { StatCardSkeleton } from '../../components/common/Skeleton';

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
      <div className="space-y-8">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {[1, 2, 3, 4].map((i) => (
            <StatCardSkeleton key={i} />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="bg-theme-card border border-theme p-8 rounded-3xl flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl transition-colors duration-200">
        <div className="space-y-2">
          <span className="text-xs uppercase font-extrabold tracking-wider text-indigo-400">
            Fleet Operations Control
          </span>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Fleet Manager Workstation</h1>
          <p className="text-sm text-theme-muted max-w-xl">
            Monitor vehicle availability, schedule preventative maintenance, and register new fleet additions.
          </p>
        </div>
        <div className="flex items-center gap-3">
          <Link
            to="/fleet/vehicles"
            className="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/25 flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            <span>Add Vehicle</span>
          </Link>
          <Link
            to="/fleet/maintenance"
            className="px-5 py-3 rounded-2xl bg-theme-secondary border border-theme hover:bg-theme-hover text-theme-secondary font-bold text-xs flex items-center gap-2 transition-colors"
          >
            <Wrench className="w-4 h-4 text-indigo-400" />
            <span>Schedule Maintenance</span>
          </Link>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-3 transition-colors duration-200">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">Total Vehicles</span>
            <div className="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20">
              <Car className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-theme-primary">{totalCount}</p>
        </div>

        <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-3 transition-colors duration-200">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">Available</span>
            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
              <CheckCircle2 className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-emerald-400">{availableCount}</p>
        </div>

        <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-3 transition-colors duration-200">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">Under Maintenance</span>
            <div className="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20">
              <Wrench className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-amber-400">{maintenanceCount}</p>
        </div>

        <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-3 transition-colors duration-200">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">Rented Out</span>
            <div className="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center border border-rose-500/20">
              <AlertTriangle className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-rose-400">{rentedCount}</p>
        </div>
      </div>

      {/* Vehicles Table */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        <div className="flex justify-between items-center pb-4 border-b border-theme">
          <div>
            <h3 className="text-lg font-bold text-theme-primary">Active Fleet Inventory</h3>
            <p className="text-xs text-theme-muted">All registered vehicles and their current status</p>
          </div>
          <Link to="/fleet/vehicles" className="text-xs text-blue-400 hover:underline">
            Manage Fleet
          </Link>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-theme-secondary">
            <thead className="text-xs uppercase bg-theme-hover text-theme-muted border-b border-theme">
              <tr>
                <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                <th className="py-3.5 px-4 font-semibold">Registration</th>
                <th className="py-3.5 px-4 font-semibold">Category</th>
                <th className="py-3.5 px-4 font-semibold">Daily Rate</th>
                <th className="py-3.5 px-4 font-semibold">Transmission</th>
                <th className="py-3.5 px-4 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-theme">
              {vehicles.slice(0, 8).map((v) => (
                <tr key={v.id} className="hover:bg-theme-hover transition-colors">
                  <td className="py-4 px-4 font-medium text-theme-primary">{v.brand} {v.model}</td>
                  <td className="py-4 px-4 font-mono text-xs text-theme-muted">{v.registration_number}</td>
                  <td className="py-4 px-4 text-theme-secondary capitalize">{v.category?.name || v.category_id || 'N/A'}</td>
                  <td className="py-4 px-4 font-bold text-emerald-400">{formatCurrency(v.rental_price_per_day)}</td>
                  <td className="py-4 px-4 text-theme-secondary capitalize">{v.transmission}</td>
                  <td className="py-4 px-4">
                    <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(v.status)}`}>
                      {formatStatus(v.status)}
                    </span>
                  </td>
                </tr>
              ))}
              {vehicles.length === 0 && (
                <tr>
                  <td colSpan="6" className="py-12 text-center text-theme-muted text-sm">
                    No vehicles found in the fleet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Recent Maintenance Records */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        <div className="flex justify-between items-center pb-4 border-b border-theme">
          <div>
            <h3 className="text-lg font-bold text-theme-primary">Recent Maintenance</h3>
            <p className="text-xs text-theme-muted">Latest scheduled and completed maintenance activities</p>
          </div>
          <Link to="/fleet/maintenance" className="text-xs text-blue-400 hover:underline">
            View All
          </Link>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-theme-secondary">
            <thead className="text-xs uppercase bg-theme-hover text-theme-muted border-b border-theme">
              <tr>
                <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                <th className="py-3.5 px-4 font-semibold">Type</th>
                <th className="py-3.5 px-4 font-semibold">Scheduled</th>
                <th className="py-3.5 px-4 font-semibold">Cost</th>
                <th className="py-3.5 px-4 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-theme">
              {maintenanceRecords.slice(0, 5).map((m) => (
                <tr key={m.id} className="hover:bg-theme-hover transition-colors">
                  <td className="py-4 px-4 font-medium text-theme-primary">
                    {m.vehicle ? `${m.vehicle.brand} ${m.vehicle.model}` : `Vehicle #${m.vehicle_id}`}
                  </td>
                  <td className="py-4 px-4 text-theme-secondary capitalize">{m.maintenance_type || m.type || 'General'}</td>
                  <td className="py-4 px-4 text-xs text-theme-muted">{formatDate(m.scheduled_date || m.created_at)}</td>
                  <td className="py-4 px-4 font-bold text-emerald-400">{formatCurrency(m.cost || 0)}</td>
                  <td className="py-4 px-4">
                    <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(m.status)}`}>
                      {formatStatus(m.status)}
                    </span>
                  </td>
                </tr>
              ))}
              {maintenanceRecords.length === 0 && (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-theme-muted text-sm">
                    No maintenance records found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default FleetDashboard;
