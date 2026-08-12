import React, { useState, useEffect, useCallback } from 'react';
import {
  Star, Search, Filter, MessageSquare, Loader2, AlertCircle,
  Eye, EyeOff, Flag, Archive, Send, X,
} from 'lucide-react';
import adminApi from '../../api/adminApi';
import { formatDate } from '../../utils/formatters';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';
import Modal from '../../components/common/Modal';
import {
  ManagementPageHeader,
  ManagementCard,
  ManagementEmptyState,
  ManagementButton,
} from '../../components/management/ManagementUI';

const STATUS_OPTIONS = [
  { value: '', label: 'All Statuses' },
  { value: 'published', label: 'Published' },
  { value: 'flagged', label: 'Flagged' },
  { value: 'hidden', label: 'Hidden' },
  { value: 'archived', label: 'Archived' },
];

const STATUS_BADGE = {
  published: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  flagged: 'bg-amber-50 text-amber-700 border-amber-200',
  hidden: 'bg-slate-100 text-slate-600 border-slate-200',
  archived: 'bg-red-50 text-red-600 border-red-200',
};

export const ReviewsManagement = () => {
  const toast = useToast();
  const [reviews, setReviews] = useState([]);
  const [stats, setStats] = useState(null);
  const [branches, setBranches] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [ratingFilter, setRatingFilter] = useState('');
  const [branchFilter, setBranchFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedReview, setSelectedReview] = useState(null);
  const [adminResponse, setAdminResponse] = useState('');
  const [responding, setResponding] = useState(false);
  const [moderating, setModerating] = useState(false);

  useEffect(() => {
    adminApi.getBranches()
      .then((r) => setBranches(Array.isArray(r.data) ? r.data : []))
      .catch(() => setBranches([]));
  }, []);

  const fetchReviews = useCallback(async () => {
    try {
      setLoading(true);
      setLoadError('');
      const params = { page, per_page: 10 };
      if (search) params.search = search;
      if (ratingFilter) params.rating = ratingFilter;
      if (branchFilter) params.branch_id = branchFilter;
      if (statusFilter) params.status = statusFilter;

      const [reviewsRes, statsRes] = await Promise.all([
        adminApi.getReviews(params),
        adminApi.getReviewStats(branchFilter ? { branch_id: branchFilter } : {}),
      ]);

      setReviews(Array.isArray(reviewsRes.data) ? reviewsRes.data : []);
      if (reviewsRes.meta) setMeta(reviewsRes.meta);
      setStats(statsRes.data || null);
    } catch (err) {
      setReviews([]);
      setLoadError(err.message || 'Unable to load reviews. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [page, ratingFilter, branchFilter, statusFilter, search]);

  useEffect(() => {
    fetchReviews();
  }, [fetchReviews]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPage(1);
    fetchReviews();
  };

  const handleStatusChange = async (review, status) => {
    try {
      setModerating(true);
      await adminApi.updateReviewStatus(review.id, { status });
      toast.success(`Review ${status}.`);
      setSelectedReview((prev) => (prev?.id === review.id ? { ...prev, status } : prev));
      fetchReviews();
    } catch (err) {
      toast.error(err.message || 'Failed to update review status.');
    } finally {
      setModerating(false);
    }
  };

  const handleRespond = async (e) => {
    e.preventDefault();
    if (!selectedReview || !adminResponse.trim()) return;
    try {
      setResponding(true);
      const res = await adminApi.respondToReview(selectedReview.id, {
        admin_response: adminResponse.trim(),
      });
      setSelectedReview(res.data);
      setAdminResponse('');
      toast.success('Response submitted.');
      fetchReviews();
    } catch (err) {
      toast.error(err.message || 'Failed to submit response.');
    } finally {
      setResponding(false);
    }
  };

  const openReview = async (review) => {
    try {
      const res = await adminApi.getReview(review.id);
      setSelectedReview(res.data);
      setAdminResponse(res.data?.admin_response || '');
    } catch {
      setSelectedReview(review);
      setAdminResponse(review.admin_response || '');
    }
  };

  const renderStars = (rating) => (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <Star
          key={star}
          className={`w-3.5 h-3.5 ${star <= rating ? 'fill-amber-400 text-amber-400' : 'text-[#CBD5E1]'}`}
        />
      ))}
    </div>
  );

  const statCards = stats ? [
    { label: 'Total Reviews', value: stats.total },
    { label: 'Published', value: stats.published },
    { label: 'Flagged', value: stats.flagged },
    { label: 'Hidden', value: stats.hidden },
    { label: 'Avg Rating', value: stats.average_rating || '—' },
  ] : [];

  return (
    <div className="space-y-8">
      <ManagementPageHeader
        title="Reviews Management"
        description="Monitor and moderate customer reviews across all branches."
      />

      {stats && (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
          {statCards.map((card) => (
            <ManagementCard key={card.label} className="text-center py-4">
              <p className="text-2xl font-extrabold text-[#0F172A]">{card.value}</p>
              <p className="text-xs text-[#64748B] mt-1">{card.label}</p>
            </ManagementCard>
          ))}
        </div>
      )}

      <ManagementCard padding={false} className="p-4 sm:p-5">
        <div className="flex flex-col lg:flex-row items-center gap-4">
          <form onSubmit={handleSearchSubmit} className="relative flex-1 w-full">
            <Search className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search by vehicle, customer, or booking..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-white border border-[#CBD5E1] rounded-xl pl-10 pr-4 py-2.5 text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:border-[#2563EB]"
            />
          </form>
          <div className="flex flex-wrap items-center gap-2 w-full lg:w-auto">
            <Filter className="w-4 h-4 text-[#64748B] shrink-0" />
            <select
              value={branchFilter}
              onChange={(e) => { setBranchFilter(e.target.value); setPage(1); }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2.5 text-xs text-[#334155] focus:outline-none focus:border-[#2563EB] min-w-[130px]"
            >
              <option value="">All Branches</option>
              {branches.map((b) => (
                <option key={b.id} value={b.id}>{b.name}</option>
              ))}
            </select>
            <select
              value={statusFilter}
              onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2.5 text-xs text-[#334155] focus:outline-none focus:border-[#2563EB]"
            >
              {STATUS_OPTIONS.map((o) => (
                <option key={o.value} value={o.value}>{o.label}</option>
              ))}
            </select>
            <select
              value={ratingFilter}
              onChange={(e) => { setRatingFilter(e.target.value); setPage(1); }}
              className="bg-white border border-[#CBD5E1] rounded-xl px-3 py-2.5 text-xs text-[#334155] focus:outline-none focus:border-[#2563EB]"
            >
              <option value="">All Ratings</option>
              {[5, 4, 3, 2, 1].map((r) => (
                <option key={r} value={r}>{r} Star{r !== 1 ? 's' : ''}</option>
              ))}
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
                  <th className="py-3.5 px-4 font-semibold">Customer</th>
                  <th className="py-3.5 px-4 font-semibold">Booking</th>
                  <th className="py-3.5 px-4 font-semibold">Vehicle</th>
                  <th className="py-3.5 px-4 font-semibold">Branch</th>
                  <th className="py-3.5 px-4 font-semibold">Rating</th>
                  <th className="py-3.5 px-4 font-semibold">Status</th>
                  <th className="py-3.5 px-4 font-semibold">Date</th>
                  <th className="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#E2E8F0]">
                {reviews.map((r) => (
                  <tr key={r.id} className="hover:bg-[#F8FAFC] transition-colors">
                    <td className="py-4 px-4">
                      <p className="font-semibold text-[#0F172A] text-xs">{r.customer?.name || r.user?.name || '—'}</p>
                      <p className="text-[10px] text-[#64748B]">{r.customer?.email || r.user?.email}</p>
                    </td>
                    <td className="py-4 px-4 text-xs font-mono text-[#64748B]">
                      {r.booking?.booking_reference || `#${r.booking_id}`}
                    </td>
                    <td className="py-4 px-4 font-medium text-[#0F172A] text-xs">
                      {r.vehicle ? `${r.vehicle.brand} ${r.vehicle.model}` : `Vehicle #${r.vehicle_id}`}
                    </td>
                    <td className="py-4 px-4 text-xs">{r.branch?.name || '—'}</td>
                    <td className="py-4 px-4">
                      <div className="flex items-center gap-1.5">
                        {renderStars(r.overall_rating || r.rating)}
                        <span className="text-xs font-bold text-amber-500">{r.overall_rating || r.rating}</span>
                      </div>
                    </td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-0.5 text-[10px] font-bold rounded-full border capitalize ${STATUS_BADGE[r.status] || STATUS_BADGE.hidden}`}>
                        {r.status}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-xs text-[#64748B]">{formatDate(r.created_at)}</td>
                    <td className="py-4 px-4">
                      <div className="flex items-center justify-end gap-1">
                        <ManagementButton variant="outline" onClick={() => openReview(r)} title="View">
                          <Eye className="w-4 h-4" />
                        </ManagementButton>
                        {r.status !== 'hidden' && (
                          <ManagementButton variant="outline" onClick={() => handleStatusChange(r, 'hidden')} title="Hide" disabled={moderating}>
                            <EyeOff className="w-4 h-4" />
                          </ManagementButton>
                        )}
                        {r.status !== 'flagged' && (
                          <ManagementButton variant="outline" onClick={() => handleStatusChange(r, 'flagged')} title="Flag" disabled={moderating}>
                            <Flag className="w-4 h-4" />
                          </ManagementButton>
                        )}
                        {r.status !== 'published' && (
                          <ManagementButton variant="outline" onClick={() => handleStatusChange(r, 'published')} title="Publish" disabled={moderating}>
                            <Star className="w-4 h-4" />
                          </ManagementButton>
                        )}
                        {r.status !== 'archived' && (
                          <ManagementButton variant="dangerOutline" onClick={() => handleStatusChange(r, 'archived')} title="Archive" disabled={moderating}>
                            <Archive className="w-4 h-4" />
                          </ManagementButton>
                        )}
                      </div>
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

      {selectedReview && (
        <Modal
          isOpen={!!selectedReview}
          onClose={() => setSelectedReview(null)}
          title="Review Details"
          maxWidth="max-w-2xl"
        >
          <div className="space-y-5 text-sm text-[#334155]">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-[10px] uppercase font-bold text-[#64748B]">Customer</p>
                <p className="font-semibold text-[#0F172A]">{selectedReview.customer?.name || selectedReview.user?.name}</p>
                <p className="text-xs">{selectedReview.customer?.email || selectedReview.user?.email}</p>
              </div>
              <div>
                <p className="text-[10px] uppercase font-bold text-[#64748B]">Booking</p>
                <p className="font-mono text-xs">{selectedReview.booking?.booking_reference}</p>
              </div>
              <div>
                <p className="text-[10px] uppercase font-bold text-[#64748B]">Vehicle</p>
                <p>{selectedReview.vehicle ? `${selectedReview.vehicle.brand} ${selectedReview.vehicle.model}` : '—'}</p>
              </div>
              <div>
                <p className="text-[10px] uppercase font-bold text-[#64748B]">Branch</p>
                <p>{selectedReview.branch?.name || '—'}</p>
              </div>
              <div>
                <p className="text-[10px] uppercase font-bold text-[#64748B]">Pickup</p>
                <p>{formatDate(selectedReview.booking?.pickup_date)}</p>
              </div>
              <div>
                <p className="text-[10px] uppercase font-bold text-[#64748B]">Return</p>
                <p>{formatDate(selectedReview.booking?.return_date)}</p>
              </div>
            </div>

            <div className="border-t border-[#E2E8F0] pt-4 space-y-2">
              <p className="text-[10px] uppercase font-bold text-[#64748B]">Ratings</p>
              {[
                ['Overall', selectedReview.overall_rating || selectedReview.rating],
                ['Vehicle', selectedReview.vehicle_rating],
                ['Cleanliness', selectedReview.cleanliness_rating],
                ['Staff', selectedReview.staff_rating],
                ['Value', selectedReview.value_rating],
              ].map(([label, val]) => val != null && (
                <div key={label} className="flex items-center justify-between">
                  <span>{label}</span>
                  <div className="flex items-center gap-2">{renderStars(val)}<span className="font-bold">{val}</span></div>
                </div>
              ))}
            </div>

            {selectedReview.comment && (
              <div className="border-t border-[#E2E8F0] pt-4">
                <p className="text-[10px] uppercase font-bold text-[#64748B] mb-1">Comment</p>
                <p className="leading-relaxed">{selectedReview.comment}</p>
              </div>
            )}

            {selectedReview.admin_response && (
              <div className="bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl p-4">
                <p className="text-[10px] uppercase font-bold text-[#64748B] mb-1">Branch Response</p>
                <p className="leading-relaxed">{selectedReview.admin_response}</p>
                {selectedReview.admin_response_at && (
                  <p className="text-[10px] text-[#64748B] mt-2">{formatDate(selectedReview.admin_response_at)}</p>
                )}
              </div>
            )}

            <form onSubmit={handleRespond} className="border-t border-[#E2E8F0] pt-4 space-y-3">
              <p className="text-[10px] uppercase font-bold text-[#64748B]">Admin / Branch Response</p>
              <textarea
                rows={3}
                value={adminResponse}
                onChange={(e) => setAdminResponse(e.target.value)}
                placeholder="Thank the customer for their feedback..."
                className="w-full border border-[#CBD5E1] rounded-xl p-3 text-sm focus:outline-none focus:border-[#2563EB]"
              />
              <ManagementButton type="submit" disabled={responding || !adminResponse.trim()}>
                <Send className="w-4 h-4 mr-1" />
                {responding ? 'Sending...' : 'Submit Response'}
              </ManagementButton>
            </form>

            <div className="flex justify-end pt-2">
              <button type="button" onClick={() => setSelectedReview(null)} className="text-xs text-[#64748B] flex items-center gap-1">
                <X className="w-3.5 h-3.5" /> Close
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
};

export default ReviewsManagement;
