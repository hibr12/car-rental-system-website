import React, { useState, useEffect } from 'react';
import { Star, Trash2, Search, Filter, MessageSquare, Loader2, AlertCircle } from 'lucide-react';
import adminApi from '../../api/adminApi';
import apiClient from '../../api/client';
import { formatDate } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

export const ReviewsManagement = () => {
  const toast = useToast();
  const [reviews, setReviews] = useState([]);
  const [branches, setBranches] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [ratingFilter, setRatingFilter] = useState('');
  const [branchFilter, setBranchFilter] = useState('');

  useEffect(() => {
    adminApi.getBranches()
      .then(r => setBranches(Array.isArray(r.data) ? r.data : []))
      .catch(() => setBranches([]));
  }, []);

  const fetchReviews = async () => {
    try {
      setLoading(true);
      setLoadError('');
      const params = { page, per_page: 10 };
      if (search) params.search = search;
      if (ratingFilter) params.rating = ratingFilter;
      if (branchFilter) params.branch_id = branchFilter;

      const res = await adminApi.getReviews(params);
      setReviews(Array.isArray(res.data) ? res.data : []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      console.error('Failed to load reviews:', err);
      setReviews([]);
      setLoadError(err.message || 'Unable to load reviews. Please try again.');
      toast.error(err.message || 'Unable to load reviews. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReviews();
  }, [page, ratingFilter, branchFilter]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPage(1);
    fetchReviews();
  };

  const handleDeleteReview = async (id) => {
    if (!window.confirm('Delete this review? This action cannot be undone.')) return;
    try {
      await apiClient.delete(`/reviews/${id}`);
      toast.success('Review deleted successfully.');
      fetchReviews();
    } catch (err) {
      toast.error(err.message || 'Failed to delete review.');
    }
  };

  const renderStars = (rating) => (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <Star
          key={star}
          className={`w-3.5 h-3.5 ${
            star <= rating ? 'fill-amber-400 text-amber-400' : 'text-[#CBD5E1]'
          }`}
        />
      ))}
    </div>
  );

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        title="Reviews Management"
        description="Monitor and manage customer reviews across all branches."
      />

      <ManagementCard padding={false} className="p-4 sm:p-5">
        <div className="flex flex-col lg:flex-row items-center gap-4">
          <form onSubmit={handleSearchSubmit} className="relative flex-1 w-full">
            <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search by vehicle or customer name..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-white border border-[#CBD5E1] rounded-xl pl-10 pr-4 py-2.5 text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:border-[#2563EB]"
            />
          </form>
          <div className="flex items-center gap-2 w-full lg:w-auto">
            <Filter className="w-4 h-4 text-[#64748B] shrink-0" />
            <select
              value={branchFilter}
              onChange={(e) => { setBranchFilter(e.target.value); setPage(1); }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2.5 text-xs text-[#334155] focus:outline-none focus:border-[#2563EB] min-w-[140px]"
            >
              <option value="">All Branches</option>
              {branches.map((b) => (
                <option key={b.id} value={b.id}>{b.name}</option>
              ))}
            </select>
            <select
              value={ratingFilter}
              onChange={(e) => { setRatingFilter(e.target.value); setPage(1); }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2.5 text-xs text-[#334155] focus:outline-none focus:border-[#2563EB]"
            >
              <option value="">All Ratings</option>
              <option value="5">5 Stars</option>
              <option value="4">4 Stars</option>
              <option value="3">3 Stars</option>
              <option value="2">2 Stars</option>
              <option value="1">1 Star</option>
            </select>
          </div>
        </div>
      </ManagementCard>

      <ManagementCard className="space-y-6">
        {loading ? (
          <div className="flex flex-col items-center justify-center py-12 text-[#64748B]">
            <Loader2 className="w-8 h-8 animate-spin mb-3 text-[#2563EB]" />
            <p className="text-sm">Loading reviews...</p>
          </div>
        ) : loadError ? (
          <div className="flex flex-col items-center justify-center py-12 text-center space-y-3">
            <AlertCircle className="w-10 h-10 text-[#DC2626]" />
            <p className="text-sm text-[#334155]">{loadError}</p>
            <ManagementButton onClick={fetchReviews}>Try Again</ManagementButton>
          </div>
        ) : reviews.length === 0 ? (
          <ManagementEmptyState icon={MessageSquare} title="No reviews yet" />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm text-[#334155]">
              <thead className="text-xs uppercase bg-[#F8FAFC] text-[#334155] border-b border-[#E2E8F0]">
                <tr>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Customer</th>
                  <th className="py-3.5 px-4 font-semibold">Branch</th>
                  <th className="py-3.5 px-4 font-semibold">Booking</th>
                  <th className="py-3.5 px-4 font-semibold">Rating</th>
                  <th className="py-3.5 px-4 font-semibold">Comment</th>
                  <th className="py-3.5 px-4 font-semibold">Date</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {reviews.map((r) => (
                  <tr key={r.id} className="hover:bg-[#F8FAFC] transition-colors">
                    <td className="py-4 px-4 font-medium text-[#0F172A]">
                      {r.vehicle ? `${r.vehicle.brand} ${r.vehicle.model}` : `Vehicle #${r.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4">
                      <div className="flex items-center gap-2">
                        <div className="w-7 h-7 rounded-full bg-blue-50 text-[#2563EB] font-bold text-[10px] flex items-center justify-center shrink-0 border border-blue-100">
                          {r.user?.name?.[0]?.toUpperCase() || 'U'}
                        </div>
                        <div>
                          <p className="font-semibold text-[#0F172A] text-xs">{r.user?.name || 'Anonymous'}</p>
                          <p className="text-[10px] text-[#64748B]">{r.user?.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="py-4 px-4 text-xs">
                      {r.branch?.name || '—'}
                    </td>
                    <td className="py-4 px-4 text-xs font-mono text-[#64748B]">
                      {r.booking?.booking_reference || `#${r.booking_id}`}
                    </td>
                    <td className="py-4 px-4">
                      <div className="flex items-center gap-2">
                        {renderStars(r.rating)}
                        <span className="text-xs font-bold text-[#F59E0B]">{r.rating}</span>
                      </div>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#334155] max-w-xs">
                      <p className="truncate">{r.comment || 'No comment'}</p>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(r.created_at)}</td>
                    <td className="py-4 px-4 text-right">
                      <ManagementButton variant="dangerOutline" onClick={() => handleDeleteReview(r.id)} title="Delete Review">
                        <Trash2 className="w-4 h-4" />
                      </ManagementButton>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {!loadError && meta.last_page > 1 && (
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(p) => setPage(p)}
          />
        )}
      </ManagementCard>
    </div>
  );
};

export default ReviewsManagement;
