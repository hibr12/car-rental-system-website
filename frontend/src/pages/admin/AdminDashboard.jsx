import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Users,
  Car,
  CalendarCheck,
  DollarSign,
  TrendingUp,
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
  Legend
} from 'recharts';
import adminApi from '../../api/adminApi';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';

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
      <div className="flex flex-col items-center justify-center py-20 text-theme-muted">
        <Loader2 className="w-10 h-10 animate-spin text-blue-500 mb-4" />
        <p className="text-sm font-medium">Loading executive dashboard statistics...</p>
      </div>
    );
  }

  const summary = stats?.summary || {};
  const monthlyRevData = stats?.monthly_revenue || [];
  const bookingStatusesData = stats?.booking_statuses || [];

  const PIE_COLORS = ['#475569', '#64748b', '#94a3b8', '#3b82f6', '#1d4ed8'];

  return (
    <div className="space-y-10">
      {/* Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-theme pb-6">
        <div>
          <span className="text-xs uppercase font-semibold tracking-wider text-theme-muted">
            Administration
          </span>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Executive Analytics</h1>
        </div>
        <div className="flex items-center gap-3">
          <Link
            to="/admin/vehicles"
            className="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs shadow-md"
          >
            Manage Vehicles
          </Link>
          <Link
            to="/admin/bookings"
            className="px-4 py-2.5 rounded-xl bg-theme-secondary border border-theme hover:bg-theme-hover text-theme-secondary font-semibold text-xs transition-colors"
          >
            Booking Desk
          </Link>
        </div>
      </div>

      {/* Metrics Row */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: 'Total Revenue',  value: formatCurrency(summary.total_revenue || 0),  sub: `Monthly: ${formatCurrency(summary.monthly_revenue || 0)}`, Icon: DollarSign },
          { label: 'Platform Users', value: summary.total_users || 0,                    sub: `Customers: ${summary.total_customers || 0}`,              Icon: Users       },
          { label: 'Vehicle Fleet',  value: summary.total_vehicles || 0,                 sub: `Available: ${summary.available_vehicles || 0} · Rented: ${summary.rented_vehicles || 0}`, Icon: Car },
          { label: 'Bookings',       value: summary.total_bookings || 0,                 sub: `Active: ${summary.active_rentals || 0} · Pending: ${summary.pending_bookings || 0}`, Icon: CalendarCheck },
        ].map(({ label, value, sub, Icon }) => (
          <div key={label} className="bg-theme-card border border-theme p-5 rounded-xl transition-colors duration-200">
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">{label}</span>
              <div className="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 flex items-center justify-center">
                <Icon className="w-4 h-4" />
              </div>
            </div>
            <p className="text-2xl font-bold text-theme-primary">{value}</p>
            <p className="text-[11px] text-theme-muted mt-1">{sub}</p>
          </div>
        ))}
      </div>

      {/* Recharts Analytics Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Monthly Revenue Area Chart */}
        <div className="lg:col-span-2 bg-theme-card border border-theme p-6 rounded-xl space-y-6 shadow-sm transition-colors duration-200">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg font-bold text-theme-primary">Revenue Growth Trend</h3>
              <p className="text-xs text-theme-muted">Monthly total revenue generated from reservations</p>
            </div>
            <TrendingUp className="w-5 h-5 text-theme-muted" />
          </div>

          <div className="h-72 w-full pt-4">
            {monthlyRevData.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={monthlyRevData}>
                  <XAxis dataKey="month" stroke="#64748b" fontSize={11} />
                  <YAxis stroke="#64748b" fontSize={11} tickFormatter={(v) => `$${v}`} />
                  <Tooltip
                    contentStyle={{ backgroundColor: 'var(--bg-card)', borderColor: 'var(--border-primary)', borderRadius: '12px', color: 'var(--text-primary)' }}
                    formatter={(val) => [formatCurrency(val), 'Revenue']}
                  />
                  <Area type="monotone" dataKey="revenue" stroke="#3b82f6" strokeWidth={3} fill="#3b82f6" fillOpacity={0.2} />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-full flex items-center justify-center text-xs text-theme-muted">No revenue data points yet.</div>
            )}
          </div>
        </div>

        {/* Booking Status Distribution Pie Chart */}
        <div className="lg:col-span-1 bg-theme-card border border-theme p-6 rounded-xl space-y-6 shadow-sm transition-colors duration-200">
          <div>
            <h3 className="text-lg font-bold text-theme-primary">Booking Statuses</h3>
            <p className="text-xs text-theme-muted">Distribution across active states</p>
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
                    contentStyle={{ backgroundColor: 'var(--bg-card)', borderColor: 'var(--border-primary)', borderRadius: '12px', color: 'var(--text-primary)' }}
                  />
                  <Legend formatter={(val) => formatStatus(val)} wrapperStyle={{ fontSize: '11px', color: 'var(--text-muted)' }} />
                </PieChart>
              </ResponsiveContainer>
            ) : (
              <div className="text-xs text-theme-muted">No booking breakdown available.</div>
            )}
          </div>
        </div>
      </div>

      {/* Recent Bookings & Recent Users Tables */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Recent Bookings */}
        <div className="bg-theme-card border border-theme p-6 rounded-xl space-y-4 shadow-sm transition-colors duration-200">
          <div className="flex items-center justify-between pb-3 border-b border-theme">
            <h3 className="text-base font-bold text-theme-primary">Recent System Bookings</h3>
            <Link to="/admin/bookings" className="text-xs text-blue-400 hover:underline">
              View All
            </Link>
          </div>
          <div className="space-y-3">
            {(stats?.recent_bookings || []).map((b) => (
              <div key={b.id} className="flex items-center justify-between p-3 bg-theme-input rounded-2xl border border-theme text-xs transition-colors duration-200">
                <div>
                  <p className="font-mono font-bold text-theme-primary">{b.booking_reference}</p>
                  <p className="font-medium text-theme-secondary">{b.vehicle?.brand} {b.vehicle?.model}</p>
                </div>
                <div className="text-right">
                  <p className="font-bold text-theme-primary">{formatCurrency(b.total_price)}</p>
                  <span className={`px-2 py-0.5 text-[10px] font-bold rounded-md border ${getStatusBadgeStyle(b.status)}`}>
                    {formatStatus(b.status)}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Recent Users */}
        <div className="bg-theme-card border border-theme p-6 rounded-xl space-y-4 shadow-sm transition-colors duration-200">
          <div className="flex items-center justify-between pb-3 border-b border-theme">
            <h3 className="text-base font-bold text-theme-primary">New User Registrations</h3>
            <Link to="/admin/users" className="text-xs text-blue-400 hover:underline">
              Manage Users
            </Link>
          </div>
          <div className="space-y-3">
            {(stats?.recent_users || []).map((u) => (
              <div key={u.id} className="flex items-center justify-between p-3 bg-theme-input rounded-2xl border border-theme text-xs transition-colors duration-200">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300 font-bold text-xs flex items-center justify-center">
                    {u.name?.[0]?.toUpperCase()}
                  </div>
                  <div>
                    <p className="font-bold text-theme-primary">{u.name}</p>
                    <p className="text-theme-muted">{u.email}</p>
                  </div>
                </div>
                <span className="px-2 py-0.5 text-[10px] uppercase font-bold rounded-full bg-theme-hover text-theme-secondary">
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
