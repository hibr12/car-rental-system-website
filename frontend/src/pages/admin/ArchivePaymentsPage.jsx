import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Archive, Search, Loader2, ArrowLeft } from 'lucide-react';
import archiveApi from '../../api/archiveApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

export const ArchivePaymentsPage = () => {
  const toast = useToast();
  const [payments, setPayments] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');

  const fetchData = async () => {
    try {
      setLoading(true);
      const res = await archiveApi.getArchivedPayments({ page, per_page: 15, search: search || undefined });
      setPayments(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch {
      toast.error('Failed to load archived payments.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [page]);

  return (
    <div className="space-y-6 bg-white">
      <div className="border-b border-[#E2E8F0] pb-6">
        <Link to="/admin/archive" className="inline-flex items-center gap-1 text-xs text-[#64748B] hover:text-[#2563EB] mb-2">
          <ArrowLeft className="w-3.5 h-3.5" /> Archive
        </Link>
        <h1 className="text-3xl font-extrabold text-[#0F172A]">Archived Payments</h1>
        <p className="text-sm text-[#64748B] mt-1">
          Failed or abandoned payment attempts archived from active views. Paid records remain in Payment History.
        </p>
      </div>

      <div className="bg-white border border-[#E2E8F0] rounded-xl overflow-hidden">
        {loading ? (
          <div className="py-16 flex justify-center"><Loader2 className="w-6 h-6 animate-spin text-[#64748B]" /></div>
        ) : payments.length === 0 ? (
          <div className="py-16 text-center">
            <Archive className="w-10 h-10 text-[#64748B] mx-auto mb-2" />
            <p className="text-sm text-[#64748B]">No archived payments found.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase text-[#64748B] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3 px-4">ID</th>
                  <th className="py-3 px-4">Reference</th>
                  <th className="py-3 px-4">Amount</th>
                  <th className="py-3 px-4">Status</th>
                  <th className="py-3 px-4">Archived</th>
                  <th className="py-3 px-4">Reason</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {payments.map((p) => (
                  <tr key={p.id} className="hover:bg-[#F8FAFC]">
                    <td className="py-3 px-4 font-mono text-xs">#{p.id}</td>
                    <td className="py-3 px-4 font-mono text-[10px]">{p.transaction_reference || '—'}</td>
                    <td className="py-3 px-4 font-semibold">{formatCurrency(p.amount)}</td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-0.5 text-[11px] font-bold rounded border ${getStatusBadgeStyle(p.status)}`}>
                        {formatStatus(p.status)}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-xs">{p.archived_at ? formatDate(p.archived_at, true) : '—'}</td>
                    <td className="py-3 px-4 text-xs text-[#64748B]">{p.archive_reason || '—'}</td>
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

export default ArchivePaymentsPage;
