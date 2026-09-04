import React, { useState, useEffect } from 'react';
import {
  Car, CalendarCheck, DollarSign, Wrench, Loader2, ArrowRight,
  CheckCircle2, AlertTriangle, Star, ClipboardList, CreditCard,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import branchApi from '../../api/branchApi';
import useAuthStore from '../../store/authStore';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { ManagementPageHeader, ManagementCard } from '../../components/management/ManagementUI';

const StatCard = ({ label, value, sub, icon: Icon, color }) => (
  <ManagementCard className="space-y-2">
    <div className="flex items-center justify-between">
      <p className="text-xs font-semibold text-[#64748B] uppercase tracking-wide">{label}</p>
      <div className={`w-8 h-8 rounded-lg ${color} flex items-center justify-center`}>
        <Icon className="w-4 h-4 text-white" />
      </div>
    </div>
    <p className="text-2xl font-bold text-[#0F172A]">{value}</p>
    {sub && <p className="text-xs text-[#64748B]">{sub}</p>}
  </ManagementCard>
);

export default function BranchDashboard() {
  const { user } = useAuthStore();
  const base = user?.role === 'branch_manager' ? '/branch' : '/manager';
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    branchApi.getDashboard()
      .then((r) => setData(r.data || null))
      .catch((err) => setError(err.message || 'Unable to load branch dashboard.'))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="mgmt-page flex flex-col items-center justify-center py-20 text-[#64748B]">
        <Loader2 className="w-10 h-10 animate-spin text-[#2563EB] mb-4" />
        <p className="text-sm font-medium">Loading branch operations…</p>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="mgmt-page py-20 text-center text-[#64748B]">
        {error || 'Failed to load dashboard.'}
      </div>
    );
  }

  const branchName = data.branch?.name || 'Branch';

  return (
    <div className="mgmt-page space-y-8">
      <ManagementPageHeader
        eyebrow="Branch Operations"
        title={`${branchName}`}
        description={data.branch?.address || 'Daily branch operations dashboard'}
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Today's Pickups" value={data.todays_pickups ?? 0} icon={CalendarCheck} color="bg-[#2563EB]" />
        <StatCard label="Today's Returns" value={data.todays_returns ?? 0} icon={CheckCircle2} color="bg-[#2563EB]" />
        <StatCard label="Pending Approvals" value={data.pending_approvals ?? 0} icon={ClipboardList} color="bg-[#F59E0B]" />
        <StatCard label="Ready for Pickup" value={data.ready_for_pickup ?? 0} icon={Car} color="bg-[#16A34A]" />
        <StatCard label="Active Rentals" value={data.active_rentals ?? 0} icon={Car} color="bg-[#2563EB]" />
        <StatCard label="Cash Verification" value={data.pending_cash_payments ?? 0} icon={CreditCard} color="bg-[#F59E0B]" />
        <StatCard label="Available Vehicles" value={data.available_vehicles ?? 0} icon={Car} color="bg-[#16A34A]" />
        <StatCard label="Needs Attention" value={data.vehicles_requiring_attention ?? 0} icon={AlertTriangle} color="bg-[#DC2626]" />
        <StatCard label="Maintenance Requests" value={data.maintenance_requests ?? 0} icon={Wrench} color="bg-[#F59E0B]" />
        <StatCard label="New Reviews" value={data.new_reviews ?? 0} icon={Star} color="bg-[#2563EB]" />
        <StatCard label="Confirmed" value={data.confirmed_bookings ?? 0} icon={CheckCircle2} color="bg-[#16A34A]" />
        <StatCard label="Today's Revenue" value={formatCurrency(data.todays_revenue || 0)} icon={DollarSign} color="bg-[#16A34A]" />
      </div>

      <ManagementCard padding={false}>
        <div className="flex items-center justify-between px-6 py-4 border-b border-[#E2E8F0]">
          <h2 className="text-sm font-bold text-[#0F172A]">Recent Bookings</h2>
          <Link to={`${base}/bookings`} className="text-xs text-[#2563EB] hover:underline flex items-center gap-1">
            View all <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>
        {(data.recent_bookings || []).length === 0 ? (
          <div className="py-10 text-center text-[#64748B] text-sm">No bookings for {branchName}.</div>
        ) : (
          <div className="divide-y divide-[#E2E8F0]">
            {data.recent_bookings.map((b) => (
              <div key={b.id} className="flex items-center justify-between px-6 py-3 hover:bg-[#F8FAFC]">
                <div>
                  <p className="text-sm font-medium text-[#0F172A]">{b.user?.name || 'Customer'}</p>
                  <p className="text-xs text-[#64748B]">{b.vehicle?.brand} {b.vehicle?.model} · {b.booking_reference}</p>
                </div>
                <div className="text-right">
                  <span className={`text-xs font-semibold px-2 py-1 rounded-lg border ${getStatusBadgeStyle(b.status)}`}>
                    {formatStatus(b.status)}
                  </span>
                  <p className="text-xs text-[#64748B] mt-1">{formatCurrency(b.total_price)}</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </ManagementCard>
    </div>
  );
}
