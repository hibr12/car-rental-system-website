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
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementButton,
} from '../../components/management/ManagementUI';

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
      <div className="flex flex-col items-center justify-center py-20 text-[#64748B]">
        <Loader2 className="w-10 h-10 animate-spin text-[#2563EB] mb-4" />
        <p className="text-sm font-medium">Loading executive dashboard statistics...</p>
      </div>
    );
  }

  const summary = stats?.summary || {};
  const monthlyRevData = stats?.monthly_revenue || [];
  const bookingStatusesData = stats?.booking_statuses || [];

  const PIE_COLORS = ['#64748B', '#94A3B8', '#CBD5E1', '#2563EB', '#1D4ED8'];
  const tooltipStyle = { backgroundColor: '#FFFFFF', borderColor: '#E2E8F0', borderRadius: '12px', color: '#0F172A' };

  return (
    <div className="space-y-10">
      <ManagementPageHeader
        eyebrow="Administration"
        title="Executive Analytics"
        actions={
          <>
            <Link to="/admin/vehicles">
              <ManagementButton>Manage Vehicles</ManagementButton>
            </Link>
            <Link to="/admin/bookings">
              <ManagementButton variant="secondary">Booking Desk</ManagementButton>
            </Link>
          </>
        }
      />

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: 'Total Revenue',  value: formatCurrency(summary.total_revenue || 0),  sub: `Monthly: ${formatCurrency(summary.monthly_revenue || 0)}`, Icon: DollarSign },
          { label: 'Platform Users', value: summary.total_users || 0,                    sub: `Customers: ${summary.total_customers || 0}`,              Icon: Users       },
          { label: 'Vehicle Fleet',  value: summary.total_vehicles || 0,                 sub: `Available: ${summary.available_vehicles || 0} · Rented: ${summary.rented_vehicles || 0}`, Icon: Car },
          { label: 'Bookings',       value: summary.total_bookings || 0,                 sub: `Active: ${summary.active_rentals || 0} · Pending: ${summary.pending_bookings || 0}`, Icon: CalendarCheck },
        ].map(({ label, value, sub, Icon }) => (
          <ManagementCard key={label}>
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-semibold uppercase tracking-wider text-[#64748B]">{label}</span>
              <div className="w-8 h-8 rounded-lg bg-[#F8FAFC] text-[#64748B] flex items-center justify-center border border-[#E2E8F0]">
                <Icon className="w-4 h-4" />
              </div>
            </div>
            <p className="text-2xl font-bold text-[#0F172A]">{value}</p>
            <p className="text-[11px] text-[#64748B] mt-1">{sub}</p>
          </ManagementCard>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <ManagementCard className="lg:col-span-2 space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg font-bold text-[#0F172A]">Revenue Growth Trend</h3>
              <p className="text-xs text-[#64748B]">Monthly total revenue generated from reservations</p>
            </div>
            <TrendingUp className="w-5 h-5 text-[#64748B]" />
          </div>

          <div className="h-72 w-full pt-4">
            {monthlyRevData.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={monthlyRevData}>
                  <XAxis dataKey="month" stroke="#64748B" fontSize={11} />
                  <YAxis stroke="#64748B" fontSize={11} tickFormatter={(v) => `$${v}`} />
                  <Tooltip contentStyle={tooltipStyle} formatter={(val) => [formatCurrency(val), 'Revenue']} />
                  <Area type="monotone" dataKey="revenue" stroke="#2563EB" strokeWidth={3} fill="#2563EB" fillOpacity={0.15} />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-full flex items-center justify-center text-xs text-[#64748B]">No revenue data points yet.</div>
            )}
          </div>
        </ManagementCard>

        <ManagementCard className="space-y-6">
          <div>
            <h3 className="text-lg font-bold text-[#0F172A]">Booking Statuses</h3>
            <p className="text-xs text-[#64748B]">Distribution across active states</p>
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
                  <Tooltip contentStyle={tooltipStyle} />
                  <Legend formatter={(val) => formatStatus(val)} wrapperStyle={{ fontSize: '11px', color: '#64748B' }} />
                </PieChart>
              </ResponsiveContainer>
            ) : (
              <div className="text-xs text-[#64748B]">No booking breakdown available.</div>
            )}
          </div>
        </ManagementCard>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <ManagementCard className="space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-[#E2E8F0]">
            <h3 className="text-base font-bold text-[#0F172A]">Recent System Bookings</h3>
            <Link to="/admin/bookings" className="text-xs text-[#2563EB] hover:underline">
              View All
            </Link>
          </div>
          <div className="space-y-3">
            {(stats?.recent_bookings || []).map((b) => (
              <div key={b.id} className="flex items-center justify-between p-3 bg-[#F8FAFC] rounded-xl border border-[#E2E8F0] text-xs">
                <div>
                  <p className="font-mono font-bold text-[#0F172A]">{b.booking_reference}</p>
                  <p className="font-medium text-[#334155]">{b.vehicle?.brand} {b.vehicle?.model}</p>
                </div>
                <div className="text-right">
                  <p className="font-bold text-[#0F172A]">{formatCurrency(b.total_price)}</p>
                  <span className={`px-2 py-0.5 text-[10px] font-bold rounded-md border ${getStatusBadgeStyle(b.status)}`}>
                    {formatStatus(b.status)}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </ManagementCard>

        <ManagementCard className="space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-[#E2E8F0]">
            <h3 className="text-base font-bold text-[#0F172A]">New User Registrations</h3>
            <Link to="/admin/users" className="text-xs text-[#2563EB] hover:underline">
              Manage Users
            </Link>
          </div>
          <div className="space-y-3">
            {(stats?.recent_users || []).map((u) => (
              <div key={u.id} className="flex items-center justify-between p-3 bg-[#F8FAFC] rounded-xl border border-[#E2E8F0] text-xs">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 rounded-full bg-blue-50 text-[#2563EB] font-bold text-xs flex items-center justify-center border border-blue-100">
                    {u.name?.[0]?.toUpperCase()}
                  </div>
                  <div>
                    <p className="font-bold text-[#0F172A]">{u.name}</p>
                    <p className="text-[#64748B]">{u.email}</p>
                  </div>
                </div>
                <span className="px-2 py-0.5 text-[10px] uppercase font-bold rounded-full bg-[#F8FAFC] text-[#334155] border border-[#E2E8F0]">
                  {u.role}
                </span>
              </div>
            ))}
          </div>
        </ManagementCard>
      </div>
    </div>
  );
};

export default AdminDashboard;
