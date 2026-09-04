import React, { useState, useEffect } from 'react';
import { History, Search, Loader2, Download, Lock } from 'lucide-react';
import paymentApi from '../../api/paymentApi';
import branchApi from '../../api/branchesApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import useAuthStore from '../../store/authStore';
import { isAdminRole } from '../../utils/roles';

export const PaymentHistoryPage = () => {
  const toast = useToast();
  const { user } = useAuthStore();
  const [payments, setPayments] = useState([]);
  const [branches, setBranches] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState({
    search: '',
    branch_id: '',
    status: '',
    payment_method: '',
    date_from: '',
    date_to: '',
  });

  useEffect(() => {
    if (isAdminRole(user?.role)) {
      branchApi.getAll().then((res) => setBranches(res.data || [])).catch(() => {});
    }
  }, [user?.role]);

  const fetchHistory = async () => {
    try {
      setLoading(true);
      const params = { page, per_page: 15, ...filters };
      Object.keys(params).forEach((k) => !params[k] && delete params[k]);
      const res = await paymentApi.getHistory(params);
      setPayments(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch {
      toast.error('Failed to load payment history.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchHistory();
  }, [page, filters.branch_id, filters.status, filters.payment_method, filters.date_from, filters.date_to]);

  const handleSearch = (e) => {
    e.preventDefault();
    setPage(1);
    fetchHistory();
  };

  const handleExport = () => {
    if (!payments.length) {
      toast.error('No data to export.');
      return;
    }
    const headers = ['ID', 'Booking', 'Customer', 'Branch', 'Amount', 'Currency', 'Method', 'Status', 'Reference', 'Receipt', 'Paid At'];
    const rows = payments.map((p) => [
      p.id,
      p.booking?.booking_reference || p.booking_id,
      p.user?.name || '',
      p.branch?.name || '',
      p.amount,
      p.currency || 'ETB',
      p.payment_method,
      p.status,
      p.transaction_reference || '',
      p.receipt_number || '',
      p.paid_at || '',
    ]);
    const csv = [headers, ...rows].map((r) => r.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `payment-history-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="space-y-6 bg-white">
      <div className="border-b border-[#E2E8F0] pb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">Payment History</h1>
          <p className="text-sm text-[#64748B] mt-1 flex items-center gap-1.5">
            <Lock className="w-3.5 h-3.5" />
            Read-only immutable financial records. Amounts and references cannot be edited here.
          </p>
        </div>
        <button
          type="button"
          onClick={handleExport}
          className="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-[#E2E8F0] text-sm font-semibold text-[#334155] hover:border-[#2563EB] hover:text-[#2563EB]"
        >
          <Download className="w-4 h-4" />
          Export CSV
        </button>
      </div>

      <form onSubmit={handleSearch} className="bg-white border border-[#E2E8F0] rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div className="relative lg:col-span-2">
          <Search className="w-4 h-4 text-[#64748B] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search reference, receipt, booking..."
            value={filters.search}
            onChange={(e) => setFilters({ ...filters, search: e.target.value })}
            className="w-full pl-9 pr-3 py-2.5 text-sm border border-[#E2E8F0] rounded-lg bg-white text-[#0F172A] focus:outline-none focus:border-[#2563EB]"
          />
        </div>
        {isAdminRole(user?.role) && (
          <select
            value={filters.branch_id}
            onChange={(e) => { setFilters({ ...filters, branch_id: e.target.value }); setPage(1); }}
            className="py-2.5 px-3 text-sm border border-[#E2E8F0] rounded-lg bg-white text-[#0F172A]"
          >
            <option value="">All Branches</option>
            {branches.map((b) => (
              <option key={b.id} value={b.id}>{b.name}</option>
            ))}
          </select>
        )}
        <select
          value={filters.status}
          onChange={(e) => { setFilters({ ...filters, status: e.target.value }); setPage(1); }}
          className="py-2.5 px-3 text-sm border border-[#E2E8F0] rounded-lg bg-white text-[#0F172A]"
        >
          <option value="">All Statuses</option>
          <option value="paid">Paid</option>
          <option value="cash_pending">Cash Pending</option>
          <option value="processing">Processing</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
          <option value="refunded">Refunded</option>
        </select>
        <select
          value={filters.payment_method}
          onChange={(e) => { setFilters({ ...filters, payment_method: e.target.value }); setPage(1); }}
          className="py-2.5 px-3 text-sm border border-[#E2E8F0] rounded-lg bg-white text-[#0F172A]"
        >
          <option value="">All Methods</option>
          <option value="online_payment">Chapa</option>
          <option value="cash">Cash</option>
        </select>
        <input
          type="date"
          value={filters.date_from}
          onChange={(e) => { setFilters({ ...filters, date_from: e.target.value }); setPage(1); }}
          className="py-2.5 px-3 text-sm border border-[#E2E8F0] rounded-lg bg-white text-[#0F172A]"
        />
        <input
          type="date"
          value={filters.date_to}
          onChange={(e) => { setFilters({ ...filters, date_to: e.target.value }); setPage(1); }}
          className="py-2.5 px-3 text-sm border border-[#E2E8F0] rounded-lg bg-white text-[#0F172A]"
        />
        <button
          type="submit"
          className="py-2.5 px-4 rounded-lg bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-semibold"
        >
          Search
        </button>
      </form>

      <div className="bg-white border border-[#E2E8F0] rounded-xl overflow-hidden">
        {loading ? (
          <div className="py-16 flex justify-center text-[#64748B]">
            <Loader2 className="w-6 h-6 animate-spin" />
          </div>
        ) : payments.length === 0 ? (
          <div className="py-16 text-center space-y-2">
            <History className="w-10 h-10 text-[#64748B] mx-auto" />
            <p className="text-sm text-[#64748B]">No payment history found.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase bg-white text-[#64748B] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3 px-4 font-semibold">Payment ID</th>
                  <th className="py-3 px-4 font-semibold">Booking</th>
                  <th className="py-3 px-4 font-semibold">Customer</th>
                  <th className="py-3 px-4 font-semibold">Branch</th>
                  <th className="py-3 px-4 font-semibold">Amount</th>
                  <th className="py-3 px-4 font-semibold">Method</th>
                  <th className="py-3 px-4 font-semibold">Status</th>
                  <th className="py-3 px-4 font-semibold">Reference</th>
                  <th className="py-3 px-4 font-semibold">Receipt</th>
                  <th className="py-3 px-4 font-semibold">Paid At</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {payments.map((p) => (
                  <tr key={p.id} className="hover:bg-[#F8FAFC]">
                    <td className="py-3 px-4 font-mono text-xs">#{p.id}</td>
                    <td className="py-3 px-4">{p.booking?.booking_reference || `#${p.booking_id}`}</td>
                    <td className="py-3 px-4">{p.user?.name || p.booking?.user?.name || '—'}</td>
                    <td className="py-3 px-4">{p.branch?.name || '—'}</td>
                    <td className="py-3 px-4 font-semibold">{formatCurrency(p.amount)}</td>
                    <td className="py-3 px-4 capitalize">{p.payment_method?.replace('_', ' ')}</td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-0.5 text-[11px] font-bold rounded border ${getStatusBadgeStyle(p.status)}`}>
                        {formatStatus(p.status)}
                      </span>
                    </td>
                    <td className="py-3 px-4 font-mono text-[10px]">{p.transaction_reference || '—'}</td>
                    <td className="py-3 px-4 font-mono text-[10px]">{p.receipt_number || '—'}</td>
                    <td className="py-3 px-4 text-xs">{p.paid_at ? formatDate(p.paid_at, true) : '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        {meta.last_page > 1 && (
          <div className="p-4 border-t border-[#E2E8F0]">
            <Pagination currentPage={meta.current_page} lastPage={meta.last_page} total={meta.total} onPageChange={setPage} />
          </div>
        )}
      </div>
    </div>
  );
};

export default PaymentHistoryPage;
