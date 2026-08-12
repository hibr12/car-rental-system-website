import React, { useState, useEffect, useCallback } from 'react';
import {
  CreditCard,
  RefreshCw,
  Loader2,
  Banknote,
  Eye,
  Archive,
  AlertTriangle,
  CheckCircle2,
  Clock,
  XCircle,
} from 'lucide-react';
import paymentApi from '../../api/paymentApi';
import useAuthStore from '../../store/authStore';
import { isAdminRole } from '../../utils/roles';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import Modal from '../../components/common/Modal';

const SUMMARY_CARDS = [
  { key: 'paid', label: 'Paid' },
  { key: 'processing', label: 'Processing' },
  { key: 'pending', label: 'Pending' },
  { key: 'failed', label: 'Failed' },
  { key: 'invalid', label: 'Invalid' },
  { key: 'amount_mismatch', label: 'Amount Mismatch' },
  { key: 'cash_awaiting', label: 'Cash Awaiting' },
  { key: 'refund_pending', label: 'Refund Pending' },
  { key: 'refunded', label: 'Refunded' },
  { key: 'disputed', label: 'Disputed' },
];

const hasAction = (p, action) => (p.allowed_actions || []).includes(action);

export const PaymentsPage = () => {
  const toast = useToast();
  const { user } = useAuthStore();
  const isAdmin = isAdminRole(user?.role);
  const [payments, setPayments] = useState([]);
  const [summary, setSummary] = useState({});
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [verifyingId, setVerifyingId] = useState(null);
  const [confirmingId, setConfirmingId] = useState(null);
  const [archivingId, setArchivingId] = useState(null);
  const [selectedPayment, setSelectedPayment] = useState(null);
  const [verifyResult, setVerifyResult] = useState(null);
  const [verifyOpen, setVerifyOpen] = useState(false);
  const [cashOpen, setCashOpen] = useState(false);
  const [cashAmount, setCashAmount] = useState('');
  const [cashTarget, setCashTarget] = useState(null);

  const fetchPayments = useCallback(async () => {
    try {
      setLoading(true);
      const res = await paymentApi.getAll({ page, per_page: 15 });
      setPayments(res.data || []);
      setSummary(res.summary || {});
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load payment records.');
    } finally {
      setLoading(false);
    }
  }, [page, toast]);

  useEffect(() => {
    fetchPayments();
  }, [fetchPayments]);

  const handleVerify = async (payment) => {
    setVerifyOpen(true);
    setVerifyResult({ loading: true, payment });
    try {
      setVerifyingId(payment.id);
      const res = await paymentApi.verifyById(payment.id);
      setVerifyResult({
        loading: false,
        payment: res.data || payment,
        message: res.message,
        code: res.code,
        success: res.success !== false,
      });
      fetchPayments();
    } catch (err) {
      setVerifyResult({
        loading: false,
        payment,
        message: err.message || 'Chapa verification failed.',
        code: err.code || 'VERIFICATION_ERROR',
        success: false,
      });
      toast.error(err.message || 'Chapa verification failed.');
    } finally {
      setVerifyingId(null);
    }
  };

  const openCashConfirm = (payment) => {
    setCashTarget(payment);
    setCashAmount(String(payment.expected_amount ?? payment.amount ?? ''));
    setCashOpen(true);
  };

  const handleConfirmCash = async (e) => {
    e.preventDefault();
    if (!cashTarget) return;
    try {
      setConfirmingId(cashTarget.id);
      const res = await paymentApi.confirmCash(cashTarget.id, {
        amount_received: Number(cashAmount),
      });
      if (res.success === false || res.code === 'PAYMENT_AMOUNT_MISMATCH') {
        toast.error(res.message || 'Cash amount mismatch.');
      } else {
        toast.success(res.message || 'Cash payment confirmed.');
      }
      setCashOpen(false);
      fetchPayments();
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

  const verifyIcon = (result) => {
    if (!result || result.loading) return <Loader2 className="w-10 h-10 text-[#2563EB] animate-spin" />;
    if (result.payment?.verification_status === 'verified') {
      return <CheckCircle2 className="w-10 h-10 text-[#16A34A]" />;
    }
    if (result.code === 'PAYMENT_AMOUNT_MISMATCH' || result.payment?.verification_status === 'amount_mismatch') {
      return <AlertTriangle className="w-10 h-10 text-[#F59E0B]" />;
    }
    if (result.payment?.verification_status === 'gateway_pending') {
      return <Clock className="w-10 h-10 text-[#2563EB]" />;
    }
    return <XCircle className="w-10 h-10 text-[#DC2626]" />;
  };

  return (
    <div className="space-y-6 bg-white">
      <div className="border-b border-[#E2E8F0] pb-6">
        <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">Payments</h1>
        <p className="text-sm text-[#64748B] mt-1">
          Backend verification is the source of truth. Financial records are never deleted — use Payment History for the full audit trail.
        </p>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
        {SUMMARY_CARDS.map((card) => (
          <div key={card.key} className="bg-white border border-[#E2E8F0] rounded-xl p-3">
            <p className="text-[10px] uppercase tracking-wide text-[#64748B] font-semibold">{card.label}</p>
            <p className="text-xl font-bold text-[#0F172A] mt-1">{summary[card.key] ?? 0}</p>
          </div>
        ))}
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
            <table className="w-full text-left text-sm text-[#334155] min-w-[1100px]">
              <thead className="text-xs uppercase bg-white text-[#64748B] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">ID</th>
                  <th className="py-3.5 px-4 font-semibold">Booking</th>
                  <th className="py-3.5 px-4 font-semibold">Customer</th>
                  <th className="py-3.5 px-4 font-semibold">Branch</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Expected</th>
                  <th className="py-3.5 px-4 font-semibold">Received</th>
                  <th className="py-3.5 px-4 font-semibold">Method</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Verification</th>
                  <th className="py-3.5 px-4 font-semibold">Date</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {payments.map((p) => (
                  <tr key={p.id} className="hover:bg-[#F8FAFC]">
                    <td className="py-4 px-4 font-mono text-xs">#{p.id}</td>
                    <td className="py-4 px-4 font-semibold text-[#0F172A]">
                      {p.booking?.booking_reference || p.booking_reference || `#${p.booking_id}`}
                    </td>
                    <td className="py-4 px-4">{p.user?.name || p.customer?.name || p.booking?.user?.name || '—'}</td>
                    <td className="py-4 px-4">{p.branch?.name || '—'}</td>
                    <td className="py-4 px-4 text-xs">
                      {p.booking?.vehicle
                        ? `${p.booking.vehicle.brand} ${p.booking.vehicle.model}`
                        : '—'}
                    </td>
                    <td className="py-4 px-4 font-extrabold text-[#0F172A]">
                      {formatCurrency(p.expected_amount ?? p.amount)}
                    </td>
                    <td className="py-4 px-4 font-semibold text-[#334155]">
                      {p.paid_amount != null ? formatCurrency(p.paid_amount) : '—'}
                    </td>
                    <td className="py-4 px-4 text-xs capitalize">{p.payment_method?.replace('_', ' ')}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(p.status)}`}>
                        {formatStatus(p.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(p.verification_status)}`}>
                        {formatStatus(p.verification_status || 'unverified')}
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
                      {hasAction(p, 'verify_chapa') && (
                        <button
                          onClick={() => handleVerify(p)}
                          disabled={verifyingId === p.id}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#2563EB] hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-semibold"
                        >
                          {verifyingId === p.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <RefreshCw className="w-3.5 h-3.5" />}
                          Verify Chapa
                        </button>
                      )}
                      {p.is_verified && p.status === 'paid' && (
                        <span className="text-[10px] font-bold text-[#16A34A]">✓ Verified</span>
                      )}
                      {hasAction(p, 'confirm_cash') && (
                        <button
                          onClick={() => openCashConfirm(p)}
                          disabled={confirmingId === p.id}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#16A34A] hover:bg-green-700 disabled:opacity-50 text-white text-xs font-semibold"
                        >
                          {confirmingId === p.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Banknote className="w-3.5 h-3.5" />}
                          Confirm Cash
                        </button>
                      )}
                      {hasAction(p, 'investigate') && (
                        <button
                          onClick={() => setSelectedPayment(p)}
                          className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#F59E0B] text-xs font-semibold text-[#F59E0B]"
                        >
                          <AlertTriangle className="w-3.5 h-3.5" /> Investigate
                        </button>
                      )}
                      {isAdmin && hasAction(p, 'archive') && (
                        <button
                          onClick={() => handleArchive(p)}
                          disabled={archivingId === p.id}
                          className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-[#E2E8F0] text-xs font-semibold text-[#334155]"
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
        <Modal isOpen={!!selectedPayment} onClose={() => setSelectedPayment(null)} title={`Payment #${selectedPayment.id}`} maxWidth="max-w-lg">
          <div className="grid grid-cols-2 gap-3 text-sm text-[#334155]">
            <p><span className="font-semibold">Booking:</span> {selectedPayment.booking?.booking_reference || selectedPayment.booking_id}</p>
            <p><span className="font-semibold">Customer:</span> {selectedPayment.user?.name || '—'}</p>
            <p><span className="font-semibold">Branch:</span> {selectedPayment.branch?.name || '—'}</p>
            <p><span className="font-semibold">Method:</span> {formatStatus(selectedPayment.payment_method)}</p>
            <p><span className="font-semibold">Expected:</span> {formatCurrency(selectedPayment.expected_amount ?? selectedPayment.amount)}</p>
            <p><span className="font-semibold">Received:</span> {selectedPayment.paid_amount != null ? formatCurrency(selectedPayment.paid_amount) : '—'}</p>
            <p><span className="font-semibold">Currency:</span> {selectedPayment.currency || 'ETB'}</p>
            <p><span className="font-semibold">Status:</span> {formatStatus(selectedPayment.status)}</p>
            <p><span className="font-semibold">Verification:</span> {formatStatus(selectedPayment.verification_status)}</p>
            <p><span className="font-semibold">Gateway:</span> {selectedPayment.gateway_status || '—'}</p>
            <p className="col-span-2"><span className="font-semibold">Tx Ref:</span> <span className="font-mono text-xs">{selectedPayment.transaction_reference || '—'}</span></p>
            <p className="col-span-2"><span className="font-semibold">Gateway Ref:</span> <span className="font-mono text-xs">{selectedPayment.gateway_reference || '—'}</span></p>
            {selectedPayment.failure_reason && (
              <p className="col-span-2 text-[#DC2626]"><span className="font-semibold">Reason:</span> {selectedPayment.failure_reason}</p>
            )}
            {selectedPayment.confirmer && (
              <p className="col-span-2"><span className="font-semibold">Confirmed by:</span> {selectedPayment.confirmer.name}</p>
            )}
          </div>
        </Modal>
      )}

      {verifyOpen && verifyResult && (
        <Modal
          isOpen={verifyOpen}
          onClose={() => setVerifyOpen(false)}
          title="Verify Chapa Payment"
          maxWidth="max-w-md"
        >
          <div className="space-y-4 text-sm text-center">
            <div className="flex justify-center">{verifyIcon(verifyResult)}</div>
            {verifyResult.loading ? (
              <p className="text-[#64748B]">Checking with Chapa API…</p>
            ) : (
              <>
                <p className="font-bold text-[#0F172A]">{verifyResult.message}</p>
                <div className="text-left space-y-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl p-4">
                  <p><span className="font-semibold">Booking:</span> {verifyResult.payment?.booking?.booking_reference || verifyResult.payment?.booking_id}</p>
                  <p><span className="font-semibold">Expected:</span> {formatCurrency(verifyResult.payment?.expected_amount ?? verifyResult.payment?.amount)}</p>
                  <p><span className="font-semibold">Received:</span> {verifyResult.payment?.paid_amount != null ? formatCurrency(verifyResult.payment.paid_amount) : '—'}</p>
                  <p><span className="font-semibold">Currency:</span> {verifyResult.payment?.currency || 'ETB'}</p>
                  <p><span className="font-semibold">Gateway Status:</span> {formatStatus(verifyResult.payment?.gateway_status || '—')}</p>
                  <p><span className="font-semibold">Verification:</span> {formatStatus(verifyResult.payment?.verification_status)}</p>
                  <p><span className="font-semibold">Source:</span> {verifyResult.payment?.verification_source || 'CHAPA_API'}</p>
                </div>
              </>
            )}
          </div>
        </Modal>
      )}

      {cashOpen && cashTarget && (
        <Modal isOpen={cashOpen} onClose={() => setCashOpen(false)} title="Confirm Cash Received" maxWidth="max-w-md">
          <form onSubmit={handleConfirmCash} className="space-y-3 text-xs">
            <p className="text-[#64748B]">
              {cashTarget.booking?.booking_reference} · Expected {formatCurrency(cashTarget.expected_amount ?? cashTarget.amount)}
            </p>
            <div>
              <label className="font-semibold text-[#334155]">Cash amount received</label>
              <input
                type="number"
                step="0.01"
                min="0"
                required
                className="w-full mt-1 border border-[#CBD5E1] rounded-xl p-2 text-[#0F172A]"
                value={cashAmount}
                onChange={(e) => setCashAmount(e.target.value)}
              />
            </div>
            <button
              type="submit"
              disabled={confirmingId === cashTarget.id}
              className="w-full py-3 rounded-xl bg-[#16A34A] text-white font-bold disabled:opacity-50"
            >
              {confirmingId === cashTarget.id ? 'Confirming…' : 'Confirm Cash Received'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
};

export default PaymentsPage;
