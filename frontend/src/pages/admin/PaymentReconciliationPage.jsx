import React, { useEffect, useState } from 'react';
import { Scale, Loader2 } from 'lucide-react';
import paymentApi from '../../api/paymentApi';
import { formatCurrency, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { useToast } from '../../components/common/Toast';

export const PaymentReconciliationPage = () => {
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState(null);

  useEffect(() => {
    paymentApi
      .getReconciliation()
      .then((res) => setData(res.data))
      .catch((err) => toast.error(err.message || 'Failed to load reconciliation.'))
      .finally(() => setLoading(false));
  }, [toast]);

  if (loading) {
    return (
      <div className="py-16 text-center text-[#64748B]">
        <Loader2 className="w-8 h-8 animate-spin mx-auto mb-2" />
        Loading reconciliation…
      </div>
    );
  }

  const items = data?.items || [];
  const totals = data?.totals || {};
  const summary = data?.summary || {};

  return (
    <div className="space-y-6 bg-white">
      <div className="border-b border-[#E2E8F0] pb-6">
        <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight flex items-center gap-2">
          <Scale className="w-7 h-7 text-[#2563EB]" />
          Payment Reconciliation
        </h1>
        <p className="text-sm text-[#64748B] mt-1">
          Exceptions requiring investigation: mismatches, unverified paid records, and branch inconsistencies.
        </p>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div className="border border-[#E2E8F0] rounded-xl p-3">
          <p className="text-[10px] uppercase text-[#64748B] font-semibold">Expected (listed)</p>
          <p className="text-lg font-bold text-[#0F172A]">{formatCurrency(totals.expected || 0)}</p>
        </div>
        <div className="border border-[#E2E8F0] rounded-xl p-3">
          <p className="text-[10px] uppercase text-[#64748B] font-semibold">Received (listed)</p>
          <p className="text-lg font-bold text-[#0F172A]">{formatCurrency(totals.received || 0)}</p>
        </div>
        <div className="border border-[#E2E8F0] rounded-xl p-3">
          <p className="text-[10px] uppercase text-[#64748B] font-semibold">Difference</p>
          <p className="text-lg font-bold text-[#DC2626]">{formatCurrency(totals.difference || 0)}</p>
        </div>
        <div className="border border-[#E2E8F0] rounded-xl p-3">
          <p className="text-[10px] uppercase text-[#64748B] font-semibold">Exceptions</p>
          <p className="text-lg font-bold text-[#0F172A]">{totals.count ?? 0}</p>
        </div>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
        {['paid', 'processing', 'invalid', 'amount_mismatch', 'failed'].map((k) => (
          <div key={k} className="border border-[#E2E8F0] rounded-lg p-2">
            <span className="text-[#64748B]">{formatStatus(k)}</span>
            <span className="float-right font-bold">{summary[k] ?? 0}</span>
          </div>
        ))}
      </div>

      <div className="border border-[#E2E8F0] rounded-xl overflow-hidden">
        {items.length === 0 ? (
          <div className="py-12 text-center text-sm text-[#64748B]">No reconciliation exceptions found.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs uppercase bg-[#F8FAFC] text-[#64748B] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3 px-4">Payment</th>
                  <th className="py-3 px-4">Booking</th>
                  <th className="py-3 px-4">Branch</th>
                  <th className="py-3 px-4">Expected</th>
                  <th className="py-3 px-4">Received</th>
                  <th className="py-3 px-4">Difference</th>
                  <th className="py-3 px-4">Gateway</th>
                  <th className="py-3 px-4">Verification</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {items.map((row) => (
                  <tr key={row.id} className="hover:bg-[#F8FAFC]">
                    <td className="py-3 px-4 font-mono text-xs">#{row.id}</td>
                    <td className="py-3 px-4 font-semibold">{row.booking_reference || '—'}</td>
                    <td className="py-3 px-4">{row.branch || '—'}</td>
                    <td className="py-3 px-4">{formatCurrency(row.expected_amount)}</td>
                    <td className="py-3 px-4">{formatCurrency(row.paid_amount)}</td>
                    <td className={`py-3 px-4 font-bold ${row.difference < 0 ? 'text-[#DC2626]' : 'text-[#F59E0B]'}`}>
                      {formatCurrency(row.difference)}
                    </td>
                    <td className="py-3 px-4 text-xs">{formatStatus(row.gateway_status || '—')}</td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(row.verification_status)}`}>
                        {formatStatus(row.verification_status)}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default PaymentReconciliationPage;
