import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Star, MessageSquare, CheckCircle2, Edit } from 'lucide-react';
import reviewApi from '../../api/reviewApi';
import StarRating from '../../components/common/StarRating';
import Modal from '../../components/common/Modal';
import { useToast } from '../../components/common/Toast';
import { formatDate } from '../../utils/formatters';

export const CustomerReviews = () => {
  const toast = useToast();
  const [completedBookings, setCompletedBookings] = useState([]);
  const [myReviews, setMyReviews] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('write');

  const [editingReview, setEditingReview] = useState(null);
  const [editRatings, setEditRatings] = useState({});
  const [editComment, setEditComment] = useState('');
  const [updating, setUpdating] = useState(false);

  const fetchCompletedBookings = async () => {
    try {
      setLoading(true);
      const res = await reviewApi.getEligibleBookings();
      setCompletedBookings(res.data || []);
    } catch {
      toast.error('Failed to load eligible rentals.');
    } finally {
      setLoading(false);
    }
  };

  const fetchMyReviews = async () => {
    try {
      const res = await reviewApi.getUserReviews();
      setMyReviews(res.data || []);
    } catch {
      toast.error('Failed to load your reviews.');
    }
  };

  useEffect(() => {
    fetchCompletedBookings();
    fetchMyReviews();
  }, []);

  const handleUpdateReview = async (e) => {
    e.preventDefault();
    if (!editingReview) return;

    try {
      setUpdating(true);
      await reviewApi.update(editingReview.id, {
        ...editRatings,
        comment: editComment.trim() || undefined,
      });
      toast.success('Review updated successfully!');
      setEditingReview(null);
      fetchMyReviews();
      fetchCompletedBookings();
    } catch (err) {
      toast.error(err.message || 'Failed to update review.');
    } finally {
      setUpdating(false);
    }
  };

  const openEdit = (review) => {
    setEditingReview(review);
    setEditRatings({
      overall_rating: review.overall_rating || review.rating,
      vehicle_rating: review.vehicle_rating || review.overall_rating || review.rating,
      cleanliness_rating: review.cleanliness_rating || review.overall_rating || review.rating,
      staff_rating: review.staff_rating || review.overall_rating || review.rating,
      value_rating: review.value_rating || review.overall_rating || review.rating,
    });
    setEditComment(review.comment || '');
  };

  return (
    <div className="space-y-8">
      <div className="border-b border-theme pb-6">
        <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Rental Reviews</h1>
        <p className="text-sm text-theme-muted">Share your experience from completed rentals.</p>
      </div>

      <div className="flex gap-2 bg-theme-secondary rounded-2xl p-1.5 border border-theme">
        <button
          onClick={() => setActiveTab('write')}
          className={`flex-1 py-2.5 rounded-xl text-xs font-bold transition-colors ${
            activeTab === 'write' ? 'bg-blue-600 text-white shadow-md' : 'text-theme-muted hover:bg-theme-hover'
          }`}
        >
          Write Review
        </button>
        <button
          onClick={() => setActiveTab('myreviews')}
          className={`flex-1 py-2.5 rounded-xl text-xs font-bold transition-colors ${
            activeTab === 'myreviews' ? 'bg-blue-600 text-white shadow-md' : 'text-theme-muted hover:bg-theme-hover'
          }`}
        >
          My Reviews
        </button>
      </div>

      {activeTab === 'write' && (
        <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
          <h3 className="text-lg font-bold text-theme-primary flex items-center gap-2">
            <Star className="w-5 h-5 text-amber-400" />
            Completed Rentals Eligible For Review
          </h3>

          {loading ? (
            <div className="py-8 text-center text-theme-muted text-sm">Loading eligible rentals...</div>
          ) : completedBookings.length === 0 ? (
            <div className="text-center py-12 space-y-3">
              <MessageSquare className="w-12 h-12 text-theme-muted mx-auto" />
              <p className="text-sm font-semibold text-theme-secondary">No rentals awaiting review</p>
              <p className="text-xs text-theme-muted max-w-xs mx-auto">
                Completed rentals you have not yet reviewed will appear here.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {completedBookings.map((b) => (
                <div key={b.id} className="bg-theme-secondary p-5 rounded-2xl border border-theme space-y-4">
                  <div>
                    <p className="text-[10px] uppercase font-bold text-blue-400 font-mono">{b.booking_reference}</p>
                    <h4 className="text-base font-bold text-theme-primary">
                      {b.vehicle ? `${b.vehicle.brand} ${b.vehicle.model}` : 'Your Vehicle'}
                    </h4>
                    <p className="text-xs text-theme-muted">{b.branch?.name}</p>
                    <p className="text-xs text-theme-muted mt-1">
                      Pickup: {formatDate(b.pickup_date)} · Return: {formatDate(b.returned_at || b.return_date)}
                    </p>
                  </div>
                  <Link
                    to={`/dashboard/bookings/${b.id}/review`}
                    className="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition-colors flex items-center justify-center gap-2"
                  >
                    <Star className="w-3.5 h-3.5" />
                    Rate Your Experience
                  </Link>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {activeTab === 'myreviews' && (
        <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
          <h3 className="text-lg font-bold text-theme-primary flex items-center gap-2">
            <CheckCircle2 className="w-5 h-5 text-green-400" />
            My Reviews
          </h3>

          {myReviews.length === 0 ? (
            <div className="text-center py-12 space-y-3">
              <MessageSquare className="w-12 h-12 text-theme-muted mx-auto" />
              <p className="text-sm font-semibold text-theme-secondary">No reviews yet</p>
            </div>
          ) : (
            <div className="space-y-4">
              {myReviews.map((review) => (
                <div key={review.id} className="bg-theme-secondary p-5 rounded-2xl border border-theme space-y-3">
                  <div className="flex justify-between items-start">
                    <div>
                      <p className="text-sm font-bold text-theme-primary">
                        {review.vehicle ? `${review.vehicle.brand} ${review.vehicle.model}` : 'Vehicle'}
                      </p>
                      <StarRating rating={review.overall_rating || review.rating} size="sm" />
                    </div>
                    {review.is_editable && (
                      <button
                        onClick={() => openEdit(review)}
                        className="p-1.5 rounded-lg hover:bg-theme-hover text-theme-muted"
                        title="Edit review"
                      >
                        <Edit className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                  {review.comment && <p className="text-sm text-theme-secondary">{review.comment}</p>}
                  <p className="text-[10px] text-theme-muted">{formatDate(review.created_at)}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {editingReview && (
        <Modal isOpen={!!editingReview} onClose={() => setEditingReview(null)} title="Edit Review" maxWidth="max-w-md">
          <form onSubmit={handleUpdateReview} className="space-y-4">
            <div>
              <label className="text-xs font-semibold text-theme-secondary">Overall Rating</label>
              <StarRating
                rating={editRatings.overall_rating || 0}
                size="lg"
                interactive
                onChange={(r) => setEditRatings((p) => ({ ...p, overall_rating: r }))}
              />
            </div>
            <textarea
              rows={4}
              value={editComment}
              onChange={(e) => setEditComment(e.target.value)}
              className="w-full bg-theme-secondary border border-theme rounded-xl p-3 text-sm"
            />
            <button type="submit" disabled={updating} className="w-full py-3 rounded-2xl bg-blue-600 text-white font-bold text-sm disabled:opacity-50">
              {updating ? 'Updating...' : 'Update Review'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
};

export default CustomerReviews;
