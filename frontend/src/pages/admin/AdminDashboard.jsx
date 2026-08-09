import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Users,
  Car,
  CalendarCheck,
  DollarSign,
  TrendingUp,
  Wrench,
  MessageSquare,
  ArrowUpRight,
  ShieldAlert,
  Loader2
} from 'lucide-react';
import {
  ResponsiveContainer,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  Tooltip,
  PieChart,
  Pie,
  Cell,
  BarChart,
  Bar,
  Legend
} from 'recharts';
import adminApi from '../../api/adminApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';

export const AdminDashboard = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    adminApi
      .getDashboard()
      .then((res) => {
        setStats(res.data || {});
      })
      .catch((err) => console.error('Failed to load admin stats:', err))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-slate-400">
        <Loader2 className="w-10 h-10 animate-spin text-blue-500 mb-4" />
        <p className="text-sm font-medium">Loading executive dashboard statistics...</p>
      </div>
    );
  }

  const summary = stats?.summary || {};
  const monthlyRevData = stats?.monthly_revenue || [];
  const bookingStatusesData = stats?.booking_statuses || [];
  const maintenanceCostsData = stats?.maintenance_costs || [];

  const PIE_COLORS = ['#3b82f6', '#10b981', '#a855f7', '#f59e0b', '#ef4444'];

  return (
    <div className="space-y-10">
      {/* Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
          <span className="text-xs uppercase font-extrabold tracking-wider text-purple-400">
            System Administration
          </span>
          <h1 className="text-3xl font-extrabold text-white tracking-tight">Executive Analytics</h1>
        </div>
        <div className="flex items-center gap-3">
          <Link
            to="/admin/vehicles"
            className="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs shadow-md shadow-blue-600/20"
          >
            Manage Vehicles
          </Link>
          <Link
            to="/admin/bookings"
            className="px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-200 font-semibold text-xs"
          >
            Booking Desk
          </Link>
        </div>
      </div>

      {/* Metrics Row */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Revenue</span>
            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
              <DollarSign className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-emerald-400">{formatCurrency(summary.total_revenue || 0)}</p>
          <p className="text-[11px] text-slate-500">Monthly: {formatCurrency(summary.monthly_revenue || 0)}</p>
        </div>

        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Platform Users</span>
            <div className="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20">
              <Users className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-white">{summary.total_users || 0}</p>
          <p className="text-[11px] text-slate-500">Customers: {summary.total_customers || 0}</p>
        </div>

        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Vehicle Fleet</span>
            <div className="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
              <Car className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-white">{summary.total_vehicles || 0}</p>
          <p className="text-[11px] text-slate-500">Available: {summary.available_vehicles || 0} | Rented: {summary.rented_vehicles || 0}</p>
        </div>

        <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Bookings Count</span>
            <div className="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
              <CalendarCheck className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-white">{summary.total_bookings || 0}</p>
          <p className="text-[11px] text-slate-500">Active: {summary.active_rentals || 0} | Pending: {summary.pending_bookings || 0}</p>
        </div>
      </div>

      {/* Recharts Analytics Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Monthly Revenue Area Chart */}
        <div className="lg:col-span-2 bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-6 shadow-xl">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg font-bold text-white">Revenue Growth Trend</h3>
              <p className="text-xs text-slate-400">Monthly total revenue generated from reservations</p>
            </div>
            <TrendingUp className="w-5 h-5 text-emerald-400" />
          </div>

          <div className="h-72 w-full pt-4">
            {monthlyRevData.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={monthlyRevData}>
                  <defs>
                    <linearGradient id="colorRev" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.4} />
                      <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <XAxis dataKey="month" stroke="#64748b" fontSize={11} />
                  <YAxis stroke="#64748b" fontSize={11} tickFormatter={(v) => `$${v}`} />
                  <Tooltip
                    contentStyle={{ backgroundColor: '#0f172a', borderColor: '#334155', borderRadius: '12px', color: '#fff' }}
                    formatter={(val) => [formatCurrency(val), 'Revenue']}
                  />
                  <Area type="monotone" dataKey="revenue" stroke="#3b82f6" strokeWidth={3} fillOpacity={1} fill="url(#colorRev)" />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-full flex items-center justify-center text-xs text-slate-500">No revenue data points yet.</div>
            )}
          </div>
        </div>

        {/* Booking Status Distribution Pie Chart */}
        <div className="lg:col-span-1 bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-6 shadow-xl">
          <div>
            <h3 className="text-lg font-bold text-white">Booking Statuses</h3>
            <p className="text-xs text-slate-400">Distribution across active states</p>
          </div>

          <div className="h-64 w-full flex items-center justify-center">
            {bookingStatusesData.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={bookingStatusesData}
                    cx="50%"
                    cy="50%"
                    innerRadius={55}
                    outerRadius={80}
                    paddingAngle={4}
                    dataKey="count"
                    nameKey="status"
                  >
                    {bookingStatusesData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={PIE_COLORS[index % PIE_COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{ backgroundColor: '#0f172a', borderColor: '#334155', borderRadius: '12px', color: '#fff' }}
                  />
                  <Legend formatter={(val) => formatStatus(val)} wrapperStyle={{ fontSize: '11px', color: '#94a3b8' }} />
                </PieChart>
              </ResponsiveContainer>
            ) : (
              <div className="text-xs text-slate-500">No booking breakdown available.</div>
            )}
          </div>
        </div>
      </div>

      {/* Recent Bookings & Recent Users Tables */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Recent Bookings */}
        <div className="bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-xl">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 className="text-base font-bold text-white">Recent System Bookings</h3>
            <Link to="/admin/bookings" className="text-xs text-blue-400 hover:underline">
              View All
            </Link>
          </div>
          <div className="space-y-3">
            {(stats?.recent_bookings || []).map((b) => (
              <div key={b.id} className="flex items-center justify-between p-3 bg-slate-950 rounded-2xl border border-slate-800/80 text-xs">
                <div>
                  <p className="font-mono font-bold text-blue-400">{b.booking_reference}</p>
                  <p className="font-medium text-slate-200">{b.vehicle?.brand} {b.vehicle?.model}</p>
                </div>
                <div className="text-right">
                  <p className="font-bold text-emerald-400">{formatCurrency(b.total_price)}</p>
                  <span className={`px-2 py-0.5 text-[10px] font-bold rounded-md border ${getStatusBadgeStyle(b.status)}`}>
                    {formatStatus(b.status)}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Recent Users */}
        <div className="bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-xl">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 className="text-base font-bold text-white">New User Registrations</h3>
            <Link to="/admin/users" className="text-xs text-blue-400 hover:underline">
              Manage Users
            </Link>
          </div>
          <div className="space-y-3">
            {(stats?.recent_users || []).map((u) => (
              <div key={u.id} className="flex items-center justify-between p-3 bg-slate-950 rounded-2xl border border-slate-800/80 text-xs">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 rounded-full bg-blue-600/30 text-blue-400 font-bold text-xs flex items-center justify-center">
                    {u.name?.[0]?.toUpperCase()}
                  </div>
                  <div>
                    <p className="font-bold text-white">{u.name}</p>
                    <p className="text-slate-400">{u.email}</p>
                  </div>
                </div>
                <span className="px-2 py-0.5 text-[10px] uppercase font-bold rounded-full bg-slate-800 text-slate-300">
                  {u.role}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;
