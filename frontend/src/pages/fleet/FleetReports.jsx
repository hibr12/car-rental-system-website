import React, { useState, useEffect } from 'react';
import { Loader2 } from 'lucide-react';
import { ResponsiveContainer, PieChart, Pie, Cell, Tooltip } from 'recharts';
import fleetApi from '../../api/fleetApi';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementButton,
} from '../../components/management/ManagementUI';

const PIE_COLORS = ['#16A34A', '#2563EB', '#DC2626', '#F59E0B', '#64748B', '#7C3AED'];

export default function FleetReports() {
  const [fleet, setFleet] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = () => {
    setLoading(true);
    setError('');
    fleetApi.getFleetReport()
      .then((res) => setFleet(res.data || null))
      .catch((err) => setError(err.message || 'Unable to load fleet report.'))
      .finally(() => setLoading(false));
  };

  useEffect(load, []);

  const pieData = fleet ? Object.entries(fleet.by_status || {}).map(([name, value]) => ({ name, value })) : [];

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        eyebrow="Fleet Analytics"
        title="Fleet Reports"
        description="Utilization and status distribution across the company fleet"
        actions={
          <ManagementButton variant="secondary" onClick={load}>
            <Loader2 className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            Refresh
          </ManagementButton>
        }
      />

      {loading ? (
        <ManagementCard className="py-16 text-center text-[#64748B]">
          <Loader2 className="w-8 h-8 animate-spin mx-auto mb-3 text-[#2563EB]" />
          Loading fleet report...
        </ManagementCard>
      ) : error ? (
        <ManagementCard className="py-12 text-center text-[#DC2626] text-sm">{error}</ManagementCard>
      ) : (
        <>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <ManagementCard><p className="text-xs text-[#64748B]">Total Vehicles</p><p className="text-3xl font-extrabold text-[#0F172A]">{fleet?.total ?? 0}</p></ManagementCard>
            <ManagementCard><p className="text-xs text-[#64748B]">Available %</p><p className="text-3xl font-extrabold text-[#16A34A]">{fleet?.available_pct ?? 0}%</p></ManagementCard>
            <ManagementCard><p className="text-xs text-[#64748B]">Rented %</p><p className="text-3xl font-extrabold text-[#DC2626]">{fleet?.rented_pct ?? 0}%</p></ManagementCard>
            <ManagementCard><p className="text-xs text-[#64748B]">Maintenance %</p><p className="text-3xl font-extrabold text-[#F59E0B]">{fleet?.maintenance_pct ?? 0}%</p></ManagementCard>
          </div>

          <ManagementCard className="h-80">
            <h3 className="text-lg font-bold text-[#0F172A] mb-4">Fleet Status Distribution</h3>
            {pieData.length === 0 ? (
              <p className="text-sm text-[#64748B] text-center py-12">No fleet data available.</p>
            ) : (
              <ResponsiveContainer width="100%" height="85%">
                <PieChart>
                  <Pie data={pieData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={100} label>
                    {pieData.map((_, i) => (
                      <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            )}
          </ManagementCard>
        </>
      )}
    </div>
  );
}
