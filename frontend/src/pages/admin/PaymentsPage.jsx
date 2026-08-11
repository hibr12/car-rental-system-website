import React, { useState, useEffect } from 'react';
import { CreditCard, RefreshCw, Loader2, Banknote, Eye, Archive } from 'lucide-react';
import paymentApi from '../../api/paymentApi';
import useAuthStore from '../../store/authStore';
import { isAdminRole } from '../../utils/roles';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

export const PaymentsPage = () => {
  const toast = useToast();
  const { user } = useAuthStore();
  const isAdmin = isAdminRole(user?.role);
  const [payments, setPayments] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [verifyingId, setVerifyingId] = useState(null);
  const [confirmingId, setConfirmingId] = useState(null);
  const [archivingId, setArchivingId] = useState(null);
  const [selectedPayment, setSelectedPayment] = useState(null);

  const ARCHIVABLE_PAYMENT_STATUSES = ['failed', 'cancelled', 'unpaid', 'pending'];

  const fetchPayments = async () => {
    try {
      setLoading(true);
      const res = await paymentApi.getAll({ page, per_page: 15 });
      setPayments(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch {
      toast.error('Failed to load payment records.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPayments();
  }, [page]);

  const handleVerify = async (payment) => {
    try {
      setVerifyingId(payment.id);
      const res = await paymentApi.verifyById(payment.id);
      toast.success(res.message || 'Payment verified with Chapa.');
      fetchPayments();
    } catch (err) {
      toast.error(err.message || 'Chapa verification failed.');
    } finally {
      setVerifyingId(null);
    }
  };

  const handleConfirmCash = async (payment) => {
    if (!window.confirm(`Confirm cash payment of ${formatCurrency(payment.amount)} received at branch?`)) {
      return;
    }
    try {
      setConfirmingId(payment.id);
      const res = await paymentApi.confirmCash(payment.id);
      toast.success(res.message || 'Cash payment confirmed.');
      fetchPayments();
      setSelectedPayment(null);
    } catch (err) {
      toast.error(err.message || 'Cash confirmation failed.');
    } finally {
      setConfirmingId(null);
    }
  };

  const handleArchive = async (payment) => {
    const reason = window.prompt('Archive reason (optional):', 'Removed from active operations list');
    if (reason === null) return;
    try {
      setArchivingId(payment.id);
      await paymentApi.archive(payment.id, reason);
      toast.success('Payment archived. Record preserved in database.');
      fetchPayments();
    } catch (err) {
      toast.error(err.message || 'Cannot archive this payment.');
    } finally {
      setArchivingId(null);
    }
  };

  return (
    <div className="space-y-6 bg-white">
      <div className="border-b border-[#E2E8F0] pb-6">
        <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">Payments</h1>
        <p className="text-sm text-[#64748B] mt-1">
          Active payment operations. Financial records are never deleted — use Payment History for the full audit trail.
        </p>
      </div>

      <div className="bg-white border border-[#E2E8F0] rounded-xl overflow-hidden">
        {loading ? (
          <div className="py-12 text-center text-[#64748B] text-sm">Loading payment records...</div>
        ) : payments.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <CreditCard className="w-12 h-12 text-[#64748B] mx-auto" />
            <p className="text-sm font-semibold text-[#0F172A]">No Payment Records Found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase bg-white text-[#64748B] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">ID</th>
                  <th className="py-3.5 px-4 font-semibold">Booking</th>
                  <th className="py-3.5 px-4 font-semibold">Customer</th>
                  <th className="py-3.5 px-4 font-semibold">Branch</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Amount</th>
                  <th className="py-3.5 px-4 font-semibold">Method</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Date</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {payments.map((p) => (
                  <tr key={p.id} className="hover:bg-[#F8FAFC]">
                    <td className="py-4 px-4 font-mono text-xs">#{p.id}</td>
                    <td className="py-4 px-4 font-semibold text-[#0F172A]">
                      {p.booking?.booking_reference || `#${p.booking_id}`}
                    </td>
                    <td className="py-4 px-4">{p.user?.name || p.booking?.user?.name || '—'}</td>
                    <td className="py-4 px-4">{p.branch?.name || '—'}</td>
                    <td className="py-4 px-4 text-xs">
                      {p.booking?.vehicle
                        ? `${p.booking.vehicle.brand} ${p.booking.vehicle.model}`
                        : '—'}
                    </td>
                    <td className="py-4 px-4 font-extrabold text-[#0F172A]">{formatCurrency(p.amount)}</td>
                    <td className="py-4 px-4 text-xs capitalize">{p.payment_method?.replace('_', ' ')}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(p.status)}`}>
                        {formatStatus(p.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(p.created_at)}</td>
                    <td className="py-4 px-4 text-right space-x-2">
                      <button
                        onClick={() => setSelectedPayment(p)}
                        className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#E2E8F0] text-xs font-semibold text-[#334155] hover:border-[#2563EB] hover:text-[#2563EB]"
                      >
                        <Eye className="w-3.5 h-3.5" /> View
                      </button>
                      {['pending', 'processing', 'failed'].includes(p.status) && p.payment_method === 'online_payment' && p.transaction_reference && (
                        <button
                          onClick={() => handleVerify(p)}
                          disabled={verifyingId === p.id}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#2563EB] hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-semibold"
                        >
                          {verifyingId === p.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <RefreshCw className="w-3.5 h-3.5" />}
                          Verify Chapa
                        </button>
                      )}
                      {p.status === 'cash_pending' && p.payment_method === 'cash' && (
                        <button
                          onClick={() => handleConfirmCash(p)}
                          disabled={confirmingId === p.id}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#16A34A] hover:bg-green-700 disabled:opacity-50 text-white text-xs font-semibold"
                        >
                          {confirmingId === p.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Banknote className="w-3.5 h-3.5" />}
                          Confirm Cash
                        </button>
                      )}
                      {isAdmin && ARCHIVABLE_PAYMENT_STATUSES.includes(p.status) && (
                        <button
                          onClick={() => handleArchive(p)}
                          disabled={archivingId === p.id}
                          className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#E2E8F0] text-xs font-semibold text-[#334155] hover:border-[#64748B]"
                          title="Archive (record preserved)"
                        >
                          {archivingId === p.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Archive className="w-3.5 h-3.5" />}
                          Archive
                        </button>
                      )}
                    </td>
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

      {selectedPayment && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onClick={() => setSelectedPayment(null)}>
          <div className="bg-white rounded-xl border border-[#E2E8F0] max-w-lg w-full p-6 space-y-4" onClick={(e) => e.stopPropagation()}>
            <h3 className="text-lg font-bold text-[#0F172A]">Payment #{selectedPayment.id}</h3>
            <div className="grid grid-cols-2 gap-3 text-sm">
              <div><span className="text-[#64748B]">Status</span><p className="font-semibold">{formatStatus(selectedPayment.status)}</p></div>
              <div><span className="text-[#64748B]">Verification</span><p className="font-semibold capitalize">{formatStatus(selectedPayment.verification_status || 'unverified')}</p></div>
              <div><span className="text-[#64748B]">Amount</span><p className="font-semibold">{formatCurrency(selectedPayment.amount)}</p></div>
              <div><span className="text-[#64748B]">Method</span><p className="font-semibold capitalize">{selectedPayment.payment_method?.replace('_', ' ')}</p></div>
              <div><span className="text-[#64748B]">Branch</span><p className="font-semibold">{selectedPayment.branch?.name || '—'}</p></div>
              {selectedPayment.transaction_reference && (
                <div className="col-span-2"><span className="text-[#64748B]">tx_ref</span><p className="font-mono text-xs">{selectedPayment.transaction_reference}</p></div>
              )}
              {selectedPayment.receipt_number && (
                <div className="col-span-2"><span className="text-[#64748B]">Receipt</span><p className="font-mono text-xs">{selectedPayment.receipt_number}</p></div>
              )}
              {selectedPayment.gateway_reference && (
                <div className="col-span-2"><span className="text-[#64748B]">Chapa Reference</span><p className="font-mono text-xs">{selectedPayment.gateway_reference}</p></div>
              )}
              {selectedPayment.verification_source && (
                <div className="col-span-2"><span className="text-[#64748B]">Verification Source</span><p className="text-xs capitalize">{selectedPayment.verification_source.replace(/_/g, ' ')}</p></div>
              )}
              {selectedPayment.paid_at && (
                <div className="col-span-2"><span className="text-[#64748B]">Paid At</span><p>{formatDate(selectedPayment.paid_at, true)}</p></div>
              )}
            </div>
            <button onClick={() => setSelectedPayment(null)} className="w-full py-2.5 rounded-lg border border-[#E2E8F0] text-sm font-semibold">Close</button>
          </div>
        </div>
      )}
    </div>
  );
};

export default PaymentsPage;
