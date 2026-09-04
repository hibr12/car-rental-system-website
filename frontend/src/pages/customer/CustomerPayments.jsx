import React, { useState, useEffect } from 'react';
import { CreditCard, Receipt } from 'lucide-react';
import paymentApi from '../../api/paymentApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

export const CustomerPayments = () => {
  const toast = useToast();
  const [payments, setPayments] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

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

  return (
    <div className="space-y-8">
      <div className="border-b border-theme pb-6">
        <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Payment History</h1>
        <p className="text-sm text-theme-muted">View your transaction references, payment methods, and financial records.</p>
      </div>

      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        {loading ? (
          <div className="py-12 text-center text-theme-muted text-sm">Loading payment records...</div>
        ) : payments.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <CreditCard className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Payment Records Found</p>
            <p className="text-xs text-theme-muted">Your payment history will appear here after booking a vehicle.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-theme-secondary">
              <thead className="text-xs uppercase bg-theme-hover text-theme-muted border-b border-theme">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Transaction Ref</th>
                  <th className="py-3.5 px-4 font-semibold">Booking</th>
                  <th className="py-3.5 px-4 font-semibold">Amount</th>
                  <th className="py-3.5 px-4 font-semibold">Method</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-theme">
                {payments.map((p) => (
                  <tr key={p.id} className="hover:bg-theme-hover transition-colors">
                    <td className="py-4 px-4 font-mono text-xs text-blue-400 font-bold">
                      {p.transaction_reference || `TXN-${p.id}`}
                    </td>
                    <td className="py-4 px-4 font-semibold text-theme-primary">
                      {p.booking?.booking_reference || `#${p.booking_id}`}
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
    </div>
  );
};

export default CustomerPayments;
