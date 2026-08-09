import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Car, Wrench, Plus, CheckCircle2, Clock, ShieldAlert } from 'lucide-react';
import vehicleApi from '../../api/vehicleApi';
import maintenanceApi from '../../api/maintenanceApi';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';

export const FleetDashboard = () => {
  const [vehicles, setVehicles] = useState([]);
  const [maintenance, setMaintenance] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([vehicleApi.getAll({ per_page: 10 }), maintenanceApi.getAll({ per_page: 5 })])
      .then(([vehRes, mainRes]) => {
        setVehicles(vehRes.data || []);
        setMaintenance(mainRes.data || []);
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  }, []);

  const totalCount = vehicles.length;
  const availableCount = vehicles.filter((v) => v.status === 'available').length;
  const maintenanceCount = vehicles.filter((v) => v.status === 'maintenance').length;
  const rentedCount = vehicles.filter((v) => v.status === 'rented').length;

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="bg-gradient-to-r from-indigo-900/60 via-slate-900 to-slate-900 border border-slate-800 p-8 rounded-3xl flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl">
        <div className="space-y-2">
          <span className="text-xs uppercase font-extrabold tracking-wider text-indigo-400">
            Fleet Operations Control
          </span>
          <h1 className="text-3xl font-extrabold text-white tracking-tight">Fleet Manager Workstation</h1>
          <p className="text-sm text-slate-400 max-w-xl">
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
            className="px-5 py-3 rounded-2xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-200 font-bold text-xs flex items-center gap-2"
          >
            <Wrench className="w-4 h-4 text-indigo-400" />
            <span>Schedule Maintenance</span>
          </Link>
        </div>
      </div>

      {/* Metrics */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-2">
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Fleet</span>
          <p className="text-3xl font-extrabold text-white">{totalCount}</p>
        </div>
        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-2">
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Ready & Available</span>
          <p className="text-3xl font-extrabold text-emerald-400">{availableCount}</p>
        </div>
        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-2">
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Currently Rented</span>
          <p className="text-3xl font-extrabold text-blue-400">{rentedCount}</p>
        </div>
        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-2">
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Under Maintenance</span>
          <p className="text-3xl font-extrabold text-rose-400">{maintenanceCount}</p>
        </div>
      </div>

      {/* Recent Vehicles Grid */}
      <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
        <div className="flex justify-between items-center pb-4 border-b border-slate-800">
          <h3 className="text-lg font-bold text-white">Active Fleet Inventory</h3>
          <Link to="/fleet/vehicles" className="text-xs text-blue-400 hover:underline">
            Manage Fleet
          </Link>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {vehicles.slice(0, 6).map((v) => (
            <div key={v.id} className="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-3">
              <div className="flex justify-between items-start">
                <div>
                  <h4 className="font-bold text-white text-base">{v.brand} {v.model}</h4>
                  <p className="text-xs text-slate-500">{v.registration_number}</p>
                </div>
                <span className={`px-2.5 py-0.5 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(v.status)}`}>
                  {formatStatus(v.status)}
                </span>
              </div>
              <div className="flex justify-between text-xs text-slate-400 pt-2 border-t border-slate-800">
                <span>Daily Rate: {formatCurrency(v.rental_price_per_day)}</span>
                <span className="capitalize">{v.transmission}</span>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default FleetDashboard;
