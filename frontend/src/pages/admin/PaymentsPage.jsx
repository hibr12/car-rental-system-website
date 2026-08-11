import React, { useState, useEffect } from 'react';
import { CreditCard, DollarSign, Search, X, AlertTriangle, Eye, Trash2 } from 'lucide-react';
import paymentApi from '../../api/paymentApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

export const PaymentsPage = () => {
  const toast = useToast();
  const [payments, setPayments] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [actionModal, setActionModal] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  const [detailModal, setDetailModal] = useState(null);
  const [detailLoading, setDetailLoading] = useState(false);

  const fetchPayments = async () => {
    try {
      setLoading(true);
      const res = await paymentApi.getAll({ page, per_page: 10 });
      setPayments(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error('Failed to load payment records.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPayments();
  }, [page]);

  const handleAction = async (payment, action) => {
    try {
      setActionLoading(true);
      if (action === 'failed') {
        await paymentApi.adminMarkAsFailed(payment.id);
        toast.success(`Payment #${payment.id} marked as failed.`);
      } else if (action === 'refund') {
        await paymentApi.adminRefund(payment.id);
        toast.success(`Payment #${payment.id} refunded successfully.`);
      } else if (action === 'delete') {
        await paymentApi.adminDelete(payment.id);
        toast.success(`Payment #${payment.id} deleted successfully.`);
      }
      setActionModal(null);
      await fetchPayments();
    } catch (err) {
      toast.error(`Failed to ${action === 'delete' ? 'delete' : action === 'failed' ? 'mark payment as failed' : 'refund payment'}.`);
    } finally {
      setActionLoading(false);
    }
  };

  const handleViewDetail = async (payment) => {
    try {
      setDetailLoading(true);
      setDetailModal(payment);
      const res = await paymentApi.getById(payment.id);
      setDetailModal(res.data);
    } catch (err) {
      toast.error('Failed to load payment details.');
      setDetailModal(null);
    } finally {
      setDetailLoading(false);
    }
  };

  return (
    <div className="space-y-8">
      <div className="border-b border-theme pb-6">
        <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Payment Transactions</h1>
        <p className="text-sm text-theme-muted">View transaction references, payment methods, and financial records.</p>
      </div>

      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
        {loading ? (
          <div className="py-12 text-center text-theme-muted text-sm">Loading payment records...</div>
        ) : payments.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <CreditCard className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Payment Records Found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-theme-secondary">
              <thead className="text-xs uppercase bg-theme-secondary text-theme-muted border-b border-theme">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Transaction Ref</th>
                  <th className="py-3.5 px-4 font-semibold">Booking ID</th>
                  <th className="py-3.5 px-4 font-semibold">Amount</th>
                  <th className="py-3.5 px-4 font-semibold">Method</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Paid Date</th>
                  <th className="py-3.5 px-4 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-theme">
                {payments.map((p) => (
                  <tr key={p.id} className="hover:bg-theme-hover transition-colors">
                    <td className="py-4 px-4 font-mono text-xs text-blue-400 font-bold">
                      {p.transaction_reference || `TXN-${p.id}`}
                    </td>
                    <td className="py-4 px-4 font-semibold text-theme-primary">
                      #{p.booking_id} {p.booking?.booking_reference ? `(${p.booking.booking_reference})` : ''}
                    </td>
                    <td className="py-4 px-4 font-extrabold text-emerald-400">
                      {formatCurrency(p.amount)}
                    </td>
                    <td className="py-4 px-4 text-xs capitalize text-theme-secondary">
                      {p.payment_method?.replace('_', ' ')}
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(p.status)}`}>
                        {formatStatus(p.status)}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-xs text-theme-muted">{formatDate(p.paid_at || p.created_at)}</td>
                    <td className="py-4 px-4">
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => handleViewDetail(p)}
                          className="px-3 py-1.5 text-[11px] font-bold rounded-lg border border-blue-500/30 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition-colors flex items-center gap-1"
                        >
                          <Eye className="w-3 h-3" />
                          View
                        </button>
                        {p.status !== 'failed' && p.status !== 'refunded' && (
                          <>
                            <button
                              onClick={() => setActionModal({ payment: p, action: 'failed' })}
                              className="px-3 py-1.5 text-[11px] font-bold rounded-lg border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors"
                            >
                              Mark Failed
                            </button>
                            <button
                              onClick={() => setActionModal({ payment: p, action: 'refund' })}
                              className="px-3 py-1.5 text-[11px] font-bold rounded-lg border border-amber-500/30 bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 transition-colors"
                            >
                              Refund
                            </button>
                          </>
                        )}
                        <button
                          onClick={() => setActionModal({ payment: p, action: 'delete' })}
                          className="px-3 py-1.5 text-[11px] font-bold rounded-lg border border-gray-500/30 bg-gray-500/10 text-gray-400 hover:bg-red-500/20 hover:text-red-400 hover:border-red-500/30 transition-colors flex items-center gap-1"
                        >
                          <Trash2 className="w-3 h-3" />
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta.last_page > 1 && (
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(p) => setPage(p)}
          />
        )}
      </div>

      {/* Action Confirmation Modal */}
      {actionModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
          <div className="bg-theme-card border border-theme rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl">
            <div className="flex items-center gap-3 mb-4">
              <div className={`p-2 rounded-xl ${
                actionModal.action === 'failed' ? 'bg-red-500/10' :
                actionModal.action === 'delete' ? 'bg-gray-500/10' : 'bg-amber-500/10'
              }`}>
                <AlertTriangle className={`w-5 h-5 ${
                  actionModal.action === 'failed' ? 'text-red-400' :
                  actionModal.action === 'delete' ? 'text-gray-400' : 'text-amber-400'
                }`} />
              </div>
              <h3 className="text-lg font-bold text-theme-primary">
                {actionModal.action === 'failed' ? 'Mark as Failed' :
                 actionModal.action === 'delete' ? 'Delete Payment Record' : 'Refund Payment'}
              </h3>
            </div>
            <p className="text-sm text-theme-secondary mb-6">
              {actionModal.action === 'failed'
                ? `Are you sure you want to mark payment #${actionModal.payment.id} as failed? This action cannot be undone.`
                : actionModal.action === 'delete'
                ? `Are you sure you want to permanently delete payment record #${actionModal.payment.id} (TXN: ${actionModal.payment.transaction_reference})? This action cannot be undone.`
                : `Are you sure you want to refund payment #${actionModal.payment.id} for ${formatCurrency(actionModal.payment.amount)}? This action cannot be undone.`
              }
            </p>
            <div className="flex items-center gap-3 justify-end">
              <button
                onClick={() => setActionModal(null)}
                disabled={actionLoading}
                className="px-4 py-2 text-sm font-semibold rounded-xl border border-theme text-theme-secondary hover:bg-theme-hover transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={() => handleAction(actionModal.payment, actionModal.action)}
                disabled={actionLoading}
                className={`px-4 py-2 text-sm font-semibold rounded-xl text-white transition-colors ${
                  actionModal.action === 'failed'
                    ? 'bg-red-500 hover:bg-red-600'
                    : actionModal.action === 'delete'
                    ? 'bg-gray-500 hover:bg-red-600'
                    : 'bg-amber-500 hover:bg-amber-600'
                } disabled:opacity-50`}
              >
                {actionLoading ? 'Processing...' :
                 actionModal.action === 'failed' ? 'Mark Failed' :
                 actionModal.action === 'delete' ? 'Delete' : 'Refund'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Payment Detail Modal */}
      {detailModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
          <div className="bg-theme-card border border-theme rounded-2xl p-6 max-w-lg w-full mx-4 shadow-2xl">
            <div className="flex items-center justify-between mb-6">
              <h3 className="text-lg font-bold text-theme-primary">Payment Details</h3>
              <button
                onClick={() => setDetailModal(null)}
                className="p-1.5 rounded-lg hover:bg-theme-hover text-theme-muted hover:text-white transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {detailLoading ? (
              <div className="py-8 text-center text-theme-muted text-sm">Loading details...</div>
            ) : (
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-1">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Payment ID</p>
                    <p className="text-sm font-bold text-theme-primary">#{detailModal.id}</p>
                  </div>
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-1">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Transaction Ref</p>
                    <p className="text-sm font-bold font-mono text-blue-400">{detailModal.transaction_reference || 'N/A'}</p>
                  </div>
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-1">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Amount</p>
                    <p className="text-sm font-bold text-emerald-400">{formatCurrency(detailModal.amount)}</p>
                  </div>
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-1">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Status</p>
                    <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(detailModal.status)}`}>
                      {formatStatus(detailModal.status)}
                    </span>
                  </div>
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-1">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Payment Method</p>
                    <p className="text-sm font-bold text-theme-primary capitalize">{detailModal.payment_method?.replace('_', ' ') || 'N/A'}</p>
                  </div>
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-1">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Booking ID</p>
                    <p className="text-sm font-bold text-theme-primary">#{detailModal.booking_id}</p>
                  </div>
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-1">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Created</p>
                    <p className="text-sm text-theme-secondary">{formatDate(detailModal.created_at)}</p>
                  </div>
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-1">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Paid At</p>
                    <p className="text-sm text-theme-secondary">{detailModal.paid_at ? formatDate(detailModal.paid_at) : 'Not paid'}</p>
                  </div>
                </div>

                {detailModal.booking && (
                  <div className="bg-theme-secondary rounded-xl p-4 space-y-2 border border-theme">
                    <p className="text-[10px] uppercase font-bold text-theme-muted">Booking Info</p>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                      <div>
                        <span className="text-theme-muted">Reference: </span>
                        <span className="font-bold text-theme-primary">{detailModal.booking.booking_reference || 'N/A'}</span>
                      </div>
                      <div>
                        <span className="text-theme-muted">Status: </span>
                        <span className="font-bold text-theme-primary capitalize">{detailModal.booking.status || 'N/A'}</span>
                      </div>
                      <div>
                        <span className="text-theme-muted">Total: </span>
                        <span className="font-bold text-emerald-400">{formatCurrency(detailModal.booking.total_price)}</span>
                      </div>
                      <div>
                        <span className="text-theme-muted">Payment Status: </span>
                        <span className="font-bold text-theme-primary capitalize">{detailModal.booking.payment_status || 'N/A'}</span>
                      </div>
                    </div>
                  </div>
                )}

                <div className="flex justify-end pt-2">
                  <button
                    onClick={() => setDetailModal(null)}
                    className="px-4 py-2 text-sm font-semibold rounded-xl border border-theme text-theme-secondary hover:bg-theme-hover transition-colors"
                  >
                    Close
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default PaymentsPage;
