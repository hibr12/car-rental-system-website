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
import {
  ManagementPageHeader,
  ManagementCard,
} from '../../components/management/ManagementUI';

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
      <div className="flex flex-col items-center justify-center py-20 text-[#64748B]">
        <Loader2 className="w-10 h-10 animate-spin text-[#2563EB] mb-4" />
        <p className="text-sm font-medium">Loading analytics data...</p>
      </div>
    );
  }

  const summary = stats?.summary || {};
  const monthlyRevData = stats?.monthly_revenue || [];
  const bookingStatusesData = stats?.booking_statuses || [];
  const vehiclePopularityData = stats?.vehicle_popularity || [];

  const PIE_COLORS = ['#64748B', '#94A3B8', '#CBD5E1', '#2563EB', '#1D4ED8'];
  const BAR_COLORS = ['#2563EB', '#64748B', '#94A3B8', '#CBD5E1', '#1D4ED8'];

  const totalRevenue = summary.total_revenue || 0;
  const totalBookings = summary.total_bookings || 0;
  const avgBookingValue = totalBookings > 0 ? totalRevenue / totalBookings : 0;
  const totalVehicles = summary.total_vehicles || 1;
  const rentedVehicles = summary.rented_vehicles || 0;
  const utilizationRate = totalVehicles > 0 ? ((rentedVehicles / totalVehicles) * 100).toFixed(1) : 0;

  const tooltipStyle = { backgroundColor: '#FFFFFF', borderColor: '#E2E8F0', borderRadius: '12px', color: '#0F172A' };

  return (
    <div className="space-y-10">
      <ManagementPageHeader
        eyebrow="Analytics"
        title="Platform Analytics"
        description="Detailed insights into revenue, bookings, and fleet performance."
      />

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: 'Total Revenue',    value: formatCurrency(totalRevenue),    sub: 'All-time platform revenue',                                                  Icon: DollarSign  },
          { label: 'Total Bookings',   value: totalBookings,                   sub: `Active: ${summary.active_rentals || 0} · Pending: ${summary.pending_bookings || 0}`, Icon: CalendarCheck },
          { label: 'Avg Booking Value',value: formatCurrency(avgBookingValue), sub: 'Revenue per booking',                                                       Icon: TrendingUp  },
          { label: 'Utilization Rate', value: `${utilizationRate}%`,           sub: `${rentedVehicles} of ${totalVehicles} vehicles rented`,                     Icon: Car         },
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
              <h3 className="text-lg font-bold text-[#0F172A]">Monthly Revenue Trend</h3>
              <p className="text-xs text-[#64748B]">Revenue generated per month</p>
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
              <div className="h-full flex items-center justify-center text-xs text-[#64748B]">No revenue data available.</div>
            )}
          </div>
        </ManagementCard>

        <ManagementCard className="space-y-6">
          <div>
            <h3 className="text-lg font-bold text-[#0F172A]">Booking Statuses</h3>
            <p className="text-xs text-[#64748B]">Distribution across states</p>
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
              <div className="text-xs text-[#64748B]">No booking data available.</div>
            )}
          </div>
        </ManagementCard>
      </div>

      <ManagementCard className="space-y-6">
        <div>
          <h3 className="text-lg font-bold text-[#0F172A]">Vehicle Popularity</h3>
          <p className="text-xs text-[#64748B]">Top vehicles by number of bookings</p>
        </div>

        <div className="h-72 w-full pt-4">
          {vehiclePopularityData.length > 0 ? (
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={vehiclePopularityData}>
                <XAxis dataKey="name" stroke="#64748B" fontSize={11} />
                <YAxis stroke="#64748B" fontSize={11} />
                <Tooltip contentStyle={tooltipStyle} />
                <Bar dataKey="bookings" radius={[6, 6, 0, 0]}>
                  {vehiclePopularityData.map((entry, index) => (
                    <Cell key={`bar-${index}`} fill={BAR_COLORS[index % BAR_COLORS.length]} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          ) : (
            <div className="h-full flex items-center justify-center text-xs text-[#64748B]">No vehicle popularity data available.</div>
          )}
        </div>
      </ManagementCard>
    </div>
  );
};

export default AnalyticsPage;
