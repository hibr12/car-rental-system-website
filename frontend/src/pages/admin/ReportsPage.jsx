import React, { useState, useEffect } from 'react';
import { Loader2 } from 'lucide-react';
import {
  ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip,
  AreaChart, Area, PieChart, Pie, Cell
} from 'recharts';
import adminApi from '../../api/adminApi';
import { formatCurrency } from '../../utils/formatters';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementButton,
} from '../../components/management/ManagementUI';

const PIE_COLORS = ['#2563EB', '#16A34A', '#F59E0B', '#DC2626', '#64748B'];
const INPUT_CLS = 'px-3 py-2 text-sm border border-[#CBD5E1] rounded-lg bg-white text-[#0F172A] focus:outline-none focus:border-[#2563EB]';

export default function ReportsPage() {
  const [revenue, setRevenue]   = useState(null);
  const [fleet, setFleet]       = useState(null);
  const [loading, setLoading]   = useState(true);
  const [from, setFrom]         = useState(() => {
    const d = new Date(); d.setMonth(d.getMonth() - 5); d.setDate(1);
    return d.toISOString().split('T')[0];
  });
  const [to, setTo] = useState(() => new Date().toISOString().split('T')[0]);

  const load = () => {
    setLoading(true);
    Promise.all([
      adminApi.getRevenueReport({ from, to }),
      adminApi.getFleetReport(),
    ]).then(([r1, r2]) => {
      setRevenue(r1.data?.data || null);
      setFleet(r2.data?.data || null);
    }).finally(() => setLoading(false));
  };

  useEffect(load, []);

  const fleetPieData = fleet ? Object.entries(fleet.by_status || {}).map(([k, v]) => ({ name: k, value: v })) : [];

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        eyebrow="Analytics"
        title="Reports"
        description="Company-wide performance overview"
      />

      <ManagementCard padding={false} className="p-4 sm:p-5">
        <div className="flex flex-wrap gap-3 items-end">
          <div>
            <label className="block text-xs font-semibold text-[#64748B] mb-1">From</label>
            <input type="date" value={from} onChange={e => setFrom(e.target.value)} className={INPUT_CLS} />
          </div>
          <div>
            <label className="block text-xs font-semibold text-[#64748B] mb-1">To</label>
            <input type="date" value={to} onChange={e => setTo(e.target.value)} className={INPUT_CLS} />
          </div>
          <ManagementButton onClick={load}>Apply</ManagementButton>
        </div>
      </ManagementCard>

      {loading ? (
        <div className="flex justify-center py-20"><Loader2 className="w-8 h-8 animate-spin text-[#2563EB]" /></div>
      ) : (
        <div className="space-y-8">
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {[
              { label: 'Total Revenue', value: formatCurrency(revenue?.total_revenue || 0), sub: 'Selected period' },
              { label: 'Total Vehicles', value: fleet?.total || 0, sub: `${fleet?.available_pct || 0}% available` },
              { label: 'Rented', value: `${fleet?.rented_pct || 0}%`, sub: 'Fleet utilization' },
              { label: 'Active Branches', value: revenue?.revenue_by_branch?.length || 0, sub: 'With revenue data' },
            ].map(({ label, value, sub }) => (
              <ManagementCard key={label}>
                <p className="text-xs font-semibold text-[#64748B] uppercase">{label}</p>
                <p className="text-2xl font-bold text-[#0F172A] mt-2">{value}</p>
                <p className="text-xs text-[#64748B] mt-1">{sub}</p>
              </ManagementCard>
            ))}
          </div>

          {revenue?.revenue_by_branch?.length > 0 && (
            <ManagementCard>
              <h2 className="text-base font-bold text-[#0F172A] mb-6">Revenue by Branch</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={revenue.revenue_by_branch}>
                    <XAxis dataKey="branch" tick={{ fontSize: 12, fill: '#64748B' }} />
                    <YAxis tick={{ fontSize: 12, fill: '#64748B' }} />
                    <Tooltip formatter={(v) => formatCurrency(v)} contentStyle={{ backgroundColor: '#FFFFFF', borderColor: '#E2E8F0', borderRadius: '12px', color: '#0F172A' }} />
                    <Bar dataKey="revenue" fill="#2563EB" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </ManagementCard>
          )}

          {revenue?.monthly_trend?.length > 0 && (
            <ManagementCard>
              <h2 className="text-base font-bold text-[#0F172A] mb-6">Monthly Revenue Trend</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={revenue.monthly_trend}>
                    <XAxis dataKey="month" tick={{ fontSize: 12, fill: '#64748B' }} />
                    <YAxis tick={{ fontSize: 12, fill: '#64748B' }} />
                    <Tooltip formatter={(v) => formatCurrency(v)} contentStyle={{ backgroundColor: '#FFFFFF', borderColor: '#E2E8F0', borderRadius: '12px', color: '#0F172A' }} />
                    <Area type="monotone" dataKey="revenue" stroke="#2563EB" fill="#DBEAFE" strokeWidth={2} />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </ManagementCard>
          )}

          {fleetPieData.length > 0 && (
            <ManagementCard>
              <h2 className="text-base font-bold text-[#0F172A] mb-6">Fleet Status Distribution</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie data={fleetPieData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={90} label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}>
                      {fleetPieData.map((_, i) => <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />)}
                    </Pie>
                    <Tooltip contentStyle={{ backgroundColor: '#FFFFFF', borderColor: '#E2E8F0', borderRadius: '12px', color: '#0F172A' }} />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </ManagementCard>
          )}
        </div>
      )}
    </div>
  );
}
