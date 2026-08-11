import React, { useState, useEffect } from 'react';
import { Star, MessageSquare, CheckCircle2, AlertCircle, Edit, Trash2 } from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import reviewApi from '../../api/reviewApi';
import StarRating from '../../components/common/StarRating';
import Modal from '../../components/common/Modal';
import { useToast } from '../../components/common/Toast';

export const CustomerReviews = () => {
  const toast = useToast();
  const [completedBookings, setCompletedBookings] = useState([]);
  const [myReviews, setMyReviews] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('write');

  const [reviewModalOpen, setReviewModalOpen] = useState(false);
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const [editingReview, setEditingReview] = useState(null);
  const [editRating, setEditRating] = useState(5);
  const [editComment, setEditComment] = useState('');
  const [updating, setUpdating] = useState(false);

  const [deleteConfirmId, setDeleteConfirmId] = useState(null);

  const fetchCompletedBookings = async () => {
    try {
      setLoading(true);
      const res = await bookingApi.getUserBookings();
      const list = res.data || [];
      setCompletedBookings(list.filter((b) => b.status === 'completed'));
    } catch (err) {
      toast.error('Failed to load completed bookings.');
    } finally {
      setLoading(false);
    }
  };

  const fetchMyReviews = async () => {
    try {
      setLoading(true);
      const res = await reviewApi.getUserReviews();
      setMyReviews(res.data || []);
    } catch (err) {
      toast.error('Failed to load your reviews.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCompletedBookings();
    fetchMyReviews();
  }, []);

  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (!selectedBooking) return;

    try {
      setSubmitting(true);
      await reviewApi.create(selectedBooking.vehicle_id, {
        booking_id: selectedBooking.id,
        rating,
        comment: comment.trim() || undefined,
      });

      toast.success('Thank you for your review!');
      setReviewModalOpen(false);
      setComment('');
      setRating(5);
      fetchMyReviews();
    } catch (err) {
      toast.error(err.message || 'Failed to submit review.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleUpdateReview = async (e) => {
    e.preventDefault();
    if (!editingReview) return;

    try {
      setUpdating(true);
      await reviewApi.update(editingReview.id, {
        rating: editRating,
        comment: editComment.trim() || undefined,
      });

      toast.success('Review updated successfully!');
      setEditingReview(null);
      setEditComment('');
      setEditRating(5);
      fetchMyReviews();
    } catch (err) {
      toast.error(err.message || 'Failed to update review.');
    } finally {
      setUpdating(false);
    }
  };

  const handleDeleteReview = async (reviewId) => {
    try {
      await reviewApi.delete(reviewId);
      toast.success('Review deleted successfully.');
      setDeleteConfirmId(null);
      fetchMyReviews();
    } catch (err) {
      toast.error(err.message || 'Failed to delete review.');
    }
  };

  return (
    <div className="space-y-8">
      <div className="border-b border-theme pb-6">
        <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Rental Reviews</h1>
        <p className="text-sm text-theme-muted">Share your experience and leave ratings for your completed trips.</p>
      </div>

      {/* Tabs */}
      <div className="flex gap-2 bg-theme-secondary rounded-2xl p-1.5 border border-theme">
        <button
          onClick={() => setActiveTab('write')}
          className={`flex-1 py-2.5 rounded-xl text-xs font-bold transition-colors ${
            activeTab === 'write'
              ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
              : 'text-theme-muted hover:bg-theme-hover'
          }`}
        >
          Write Review
        </button>
        <button
          onClick={() => setActiveTab('myreviews')}
          className={`flex-1 py-2.5 rounded-xl text-xs font-bold transition-colors ${
            activeTab === 'myreviews'
              ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
              : 'text-theme-muted hover:bg-theme-hover'
          }`}
        >
          My Reviews
        </button>
      </div>

      {activeTab === 'write' && (
        <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
          <h3 className="text-lg font-bold text-theme-primary flex items-center gap-2">
            <Star className="w-5 h-5 text-amber-400" />
            <span>Completed Rentals Eligible For Review</span>
          </h3>

          {loading ? (
            <div className="py-8 text-center text-theme-muted text-sm">Loading eligible rentals...</div>
          ) : completedBookings.length === 0 ? (
            <div className="text-center py-12 space-y-3">
              <MessageSquare className="w-12 h-12 text-theme-muted mx-auto" />
              <p className="text-sm font-semibold text-theme-secondary">No Completed Rentals Yet</p>
              <p className="text-xs text-theme-muted max-w-xs mx-auto">
                Once you complete a rental reservation, you can return here to leave ratings and feedback.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {completedBookings.map((b) => (
                <div key={b.id} className="bg-theme-secondary p-5 rounded-2xl border border-theme space-y-4">
                  <div className="flex justify-between items-start">
                    <div>
                      <span className="text-[10px] uppercase font-bold text-blue-400 font-mono">
                        Ref: {b.booking_reference}
                      </span>
                      <h4 className="text-base font-bold text-theme-primary">
                        {b.vehicle ? `${b.vehicle.brand} ${b.vehicle.model}` : `Vehicle #${b.vehicle_id}`}
                      </h4>
                    </div>
                    <span className="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20">
                      Completed
                    </span>
                  </div>

                  <button
                    onClick={() => {
                      setSelectedBooking(b);
                      setReviewModalOpen(true);
                    }}
                    className="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition-colors shadow-md shadow-blue-600/20 flex items-center justify-center gap-2"
                  >
                    <Star className="w-3.5 h-3.5" />
                    <span>Leave Review & Rating</span>
                  </button>
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
            <span>My Reviews</span>
          </h3>

          {loading ? (
            <div className="py-8 text-center text-theme-muted text-sm">Loading your reviews...</div>
          ) : myReviews.length === 0 ? (
            <div className="text-center py-12 space-y-3">
              <MessageSquare className="w-12 h-12 text-theme-muted mx-auto" />
              <p className="text-sm font-semibold text-theme-secondary">No Reviews Yet</p>
              <p className="text-xs text-theme-muted max-w-xs mx-auto">
                Complete a rental and leave a review. It will appear here.
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {myReviews.map((review) => (
                <div key={review.id} className="bg-theme-secondary p-5 rounded-2xl border border-theme space-y-3">
                  <div className="flex justify-between items-start">
                    <div className="space-y-1">
                      <span className="text-[10px] uppercase font-bold text-blue-400 font-mono">
                        {review.vehicle ? `${review.vehicle.brand} ${review.vehicle.model}` : `Vehicle #${review.vehicle_id}`}
                      </span>
                      <div className="flex items-center gap-1">
                        {[1, 2, 3, 4, 5].map((s) => (
                          <Star
                            key={s}
                            className={`w-3.5 h-3.5 ${
                              s <= review.rating ? 'text-amber-400 fill-amber-400' : 'text-theme-muted'
                            }`}
                          />
                        ))}
                      </div>
                    </div>
                    <div className="flex gap-1.5">
                      <button
                        onClick={() => {
                          setEditingReview(review);
                          setEditRating(review.rating);
                          setEditComment(review.comment || '');
                        }}
                        className="p-1.5 rounded-lg hover:bg-theme-hover text-theme-muted hover:text-white transition-colors"
                        title="Edit review"
                      >
                        <Edit className="w-3.5 h-3.5" />
                      </button>
                      <button
                        onClick={() => setDeleteConfirmId(review.id)}
                        className="p-1.5 rounded-lg hover:bg-theme-hover text-theme-muted hover:text-red-400 transition-colors"
                        title="Delete review"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>

                  {review.comment && (
                    <p className="text-sm text-theme-secondary leading-relaxed">{review.comment}</p>
                  )}

                  {review.created_at && (
                    <p className="text-[10px] text-theme-muted">
                      {new Date(review.created_at).toLocaleDateString()}
                    </p>
                  )}

                  {/* Inline delete confirmation */}
                  {deleteConfirmId === review.id && (
                    <div className="flex items-center gap-2 bg-red-500/10 border border-red-500/20 rounded-xl p-3">
                      <AlertCircle className="w-4 h-4 text-red-400 shrink-0" />
                      <p className="text-xs text-red-400 flex-1">Delete this review?</p>
                      <button
                        onClick={() => setDeleteConfirmId(null)}
                        className="px-3 py-1 text-[10px] font-bold rounded-lg bg-theme-card border border-theme text-theme-secondary hover:bg-theme-hover transition-colors"
                      >
                        Cancel
                      </button>
                      <button
                        onClick={() => handleDeleteReview(review.id)}
                        className="px-3 py-1 text-[10px] font-bold rounded-lg bg-red-600 text-white hover:bg-red-500 transition-colors"
                      >
                        Delete
                      </button>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Write Review Modal */}
      {selectedBooking && (
        <Modal
          isOpen={reviewModalOpen}
          onClose={() => setReviewModalOpen(false)}
          title={`Review Vehicle #${selectedBooking.booking_reference}`}
          maxWidth="max-w-md"
        >
          <form onSubmit={handleSubmitReview} className="space-y-5">
            <div className="text-center space-y-2">
              <label className="block text-xs font-semibold text-theme-secondary">Select Rating</label>
              <div className="flex justify-center py-2">
                <StarRating rating={rating} size="lg" interactive onChange={(r) => setRating(r)} />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Your Review / Comments</label>
              <textarea
                rows="4"
                placeholder="Describe your driving experience, vehicle cleanliness, staff service..."
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                className="w-full bg-theme-secondary border border-theme rounded-xl p-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
              />
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="w-full py-3.5 rounded-2xl bg-blue-600 text-white font-bold text-sm shadow-lg shadow-blue-600/25 disabled:opacity-50"
            >
              {submitting ? 'Submitting...' : 'Post Vehicle Review'}
            </button>
          </form>
        </Modal>
      )}

      {/* Edit Review Modal */}
      {editingReview && (
        <Modal
          isOpen={!!editingReview}
          onClose={() => setEditingReview(null)}
          title="Edit Review"
          maxWidth="max-w-md"
        >
          <form onSubmit={handleUpdateReview} className="space-y-5">
            <div className="text-center space-y-2">
              <label className="block text-xs font-semibold text-theme-secondary">Select Rating</label>
              <div className="flex justify-center py-2">
                <StarRating rating={editRating} size="lg" interactive onChange={(r) => setEditRating(r)} />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Your Review / Comments</label>
              <textarea
                rows="4"
                placeholder="Describe your driving experience, vehicle cleanliness, staff service..."
                value={editComment}
                onChange={(e) => setEditComment(e.target.value)}
                className="w-full bg-theme-secondary border border-theme rounded-xl p-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
              />
            </div>

            <button
              type="submit"
              disabled={updating}
              className="w-full py-3.5 rounded-2xl bg-blue-600 text-white font-bold text-sm shadow-lg shadow-blue-600/25 disabled:opacity-50"
            >
              {updating ? 'Updating...' : 'Update Review'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
};

export default CustomerReviews;
