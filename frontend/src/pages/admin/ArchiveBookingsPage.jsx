import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Archive, Search, Loader2, ArrowLeft } from 'lucide-react';
import archiveApi from '../../api/archiveApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

const ARCHIVABLE = ['completed', 'cancelled', 'rejected', 'expired'];

export const ArchiveBookingsPage = () => {
  const toast = useToast();
  const [bookings, setBookings] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');

  const fetchData = async () => {
    try {
      setLoading(true);
      const res = await archiveApi.getArchivedBookings({ page, per_page: 15, search: search || undefined });
      setBookings(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch {
      toast.error('Failed to load archived bookings.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [page]);

  const handleSearch = (e) => {
    e.preventDefault();
    setPage(1);
    fetchData();
  };

  return (
    <div className="space-y-6 bg-white">
      <div className="flex items-start justify-between gap-4 border-b border-[#E2E8F0] pb-6">
        <div>
          <Link to="/admin/archive" className="inline-flex items-center gap-1 text-xs text-[#64748B] hover:text-[#2563EB] mb-2">
            <ArrowLeft className="w-3.5 h-3.5" /> Archive
          </Link>
          <h1 className="text-3xl font-extrabold text-[#0F172A]">Archived Bookings</h1>
          <p className="text-sm text-[#64748B] mt-1">Preserved records — not permanently deleted.</p>
        </div>
      </div>

      <form onSubmit={handleSearch} className="flex gap-2">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-[#64748B] absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search reference, customer..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-2.5 text-sm border border-[#E2E8F0] rounded-lg bg-white"
          />
        </div>
        <button type="submit" className="px-4 py-2.5 rounded-lg bg-[#2563EB] text-white text-sm font-semibold">Search</button>
      </form>

      <div className="bg-white border border-[#E2E8F0] rounded-xl overflow-hidden">
        {loading ? (
          <div className="py-16 flex justify-center"><Loader2 className="w-6 h-6 animate-spin text-[#64748B]" /></div>
        ) : bookings.length === 0 ? (
          <div className="py-16 text-center">
            <Archive className="w-10 h-10 text-[#64748B] mx-auto mb-2" />
            <p className="text-sm text-[#64748B]">No archived bookings found.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase text-[#64748B] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3 px-4">Reference</th>
                  <th className="py-3 px-4">Customer</th>
                  <th className="py-3 px-4">Status</th>
                  <th className="py-3 px-4">Total</th>
                  <th className="py-3 px-4">Archived</th>
                  <th className="py-3 px-4">Reason</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {bookings.map((b) => (
                  <tr key={b.id} className="hover:bg-[#F8FAFC]">
                    <td className="py-3 px-4 font-mono text-xs">{b.booking_reference}</td>
                    <td className="py-3 px-4">{b.user?.name || '—'}</td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-0.5 text-[11px] font-bold rounded border ${getStatusBadgeStyle(b.status)}`}>
                        {formatStatus(b.status)}
                      </span>
                    </td>
                    <td className="py-3 px-4 font-semibold">{formatCurrency(b.total_price)}</td>
                    <td className="py-3 px-4 text-xs">{b.archived_at ? formatDate(b.archived_at, true) : '—'}</td>
                    <td className="py-3 px-4 text-xs text-[#64748B]">{b.archive_reason || '—'}</td>
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

export { ARCHIVABLE };
export default ArchiveBookingsPage;
