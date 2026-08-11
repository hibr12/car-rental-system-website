import React, { useState, useEffect } from 'react';
import {
  DollarSign,
  CalendarCheck,
  Car,
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
  Legend,
  BarChart,
  Bar
} from 'recharts';
import adminApi from '../../api/adminApi';
import { formatCurrency, formatStatus } from '../../utils/formatters';

export const AnalyticsPage = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    adminApi
      .getDashboard()
      .then((res) => {
        setStats(res.data || {});
      })
      .catch((err) => console.error('Failed to load analytics:', err))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-theme-muted">
        <Loader2 className="w-10 h-10 animate-spin text-blue-500 mb-4" />
        <p className="text-sm font-medium">Loading analytics data...</p>
      </div>
    );
  }

  const summary = stats?.summary || {};
  const monthlyRevData = stats?.monthly_revenue || [];
  const bookingStatusesData = stats?.booking_statuses || [];
  const vehiclePopularityData = stats?.vehicle_popularity || [];

  const PIE_COLORS = ['#3b82f6', '#10b981', '#a855f7', '#f59e0b', '#ef4444'];
  const BAR_COLORS = ['#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef'];

  const totalRevenue = summary.total_revenue || 0;
  const totalBookings = summary.total_bookings || 0;
  const avgBookingValue = totalBookings > 0 ? totalRevenue / totalBookings : 0;
  const totalVehicles = summary.total_vehicles || 1;
  const rentedVehicles = summary.rented_vehicles || 0;
  const utilizationRate = totalVehicles > 0 ? ((rentedVehicles / totalVehicles) * 100).toFixed(1) : 0;

  return (
    <div className="space-y-10">
      {/* Title */}
      <div className="border-b border-theme pb-6">
        <span className="text-xs uppercase font-extrabold tracking-wider text-purple-400">
          Business Intelligence
        </span>
        <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Platform Analytics</h1>
        <p className="text-sm text-theme-muted mt-1">Detailed insights into revenue, bookings, and fleet performance.</p>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-3 transition-colors duration-200">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">Total Revenue</span>
            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
              <DollarSign className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-emerald-400">{formatCurrency(totalRevenue)}</p>
          <p className="text-[11px] text-theme-muted">All-time platform revenue</p>
        </div>

        <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-3 transition-colors duration-200">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">Total Bookings</span>
            <div className="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20">
              <CalendarCheck className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-theme-primary">{totalBookings}</p>
          <p className="text-[11px] text-theme-muted">Active: {summary.active_rentals || 0} | Pending: {summary.pending_bookings || 0}</p>
        </div>

        <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-3 transition-colors duration-200">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">Avg Booking Value</span>
            <div className="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
              <TrendingUp className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-indigo-400">{formatCurrency(avgBookingValue)}</p>
          <p className="text-[11px] text-theme-muted">Revenue per completed booking</p>
        </div>

        <div className="bg-theme-card border border-theme p-6 rounded-2xl space-y-3 transition-colors duration-200">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wider text-theme-muted">Utilization Rate</span>
            <div className="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
              <Car className="w-5 h-5" />
            </div>
          </div>
          <p className="text-3xl font-extrabold text-purple-400">{utilizationRate}%</p>
          <p className="text-[11px] text-theme-muted">{rentedVehicles} of {totalVehicles} vehicles rented</p>
        </div>
      </div>

      {/* Charts Section - Revenue & Booking Status */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Monthly Revenue Area Chart */}
        <div className="lg:col-span-2 bg-theme-card border border-theme p-6 rounded-3xl space-y-6 shadow-xl transition-colors duration-200">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg font-bold text-theme-primary">Monthly Revenue Trend</h3>
              <p className="text-xs text-theme-muted">Revenue generated per month</p>
            </div>
            <TrendingUp className="w-5 h-5 text-emerald-400" />
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
              <div className="h-full flex items-center justify-center text-xs text-theme-muted">No revenue data available.</div>
            )}
          </div>
        </div>

        {/* Booking Status Distribution Pie Chart */}
        <div className="lg:col-span-1 bg-theme-card border border-theme p-6 rounded-3xl space-y-6 shadow-xl transition-colors duration-200">
          <div>
            <h3 className="text-lg font-bold text-theme-primary">Booking Statuses</h3>
            <p className="text-xs text-theme-muted">Distribution across states</p>
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
              <div className="text-xs text-theme-muted">No booking data available.</div>
            )}
          </div>
        </div>
      </div>

      {/* Vehicle Popularity Bar Chart */}
      <div className="bg-theme-card border border-theme p-6 rounded-3xl space-y-6 shadow-xl transition-colors duration-200">
        <div>
          <h3 className="text-lg font-bold text-theme-primary">Vehicle Popularity</h3>
          <p className="text-xs text-theme-muted">Top vehicles by number of bookings</p>
        </div>

        <div className="h-72 w-full pt-4">
          {vehiclePopularityData.length > 0 ? (
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={vehiclePopularityData}>
                <XAxis dataKey="name" stroke="#64748b" fontSize={11} />
                <YAxis stroke="#64748b" fontSize={11} />
                <Tooltip
                  contentStyle={{ backgroundColor: 'var(--bg-card)', borderColor: 'var(--border-primary)', borderRadius: '12px', color: 'var(--text-primary)' }}
                />
                <Bar dataKey="bookings" radius={[6, 6, 0, 0]}>
                  {vehiclePopularityData.map((entry, index) => (
                    <Cell key={`bar-${index}`} fill={BAR_COLORS[index % BAR_COLORS.length]} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <div className="h-full flex items-center justify-center text-xs text-theme-muted">No vehicle popularity data available.</div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AnalyticsPage;
