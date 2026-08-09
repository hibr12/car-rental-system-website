import React, { useState, useEffect } from 'react';
import { Star, MessageSquare, CheckCircle2, AlertCircle } from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import reviewApi from '../../api/reviewApi';
import StarRating from '../../components/common/StarRating';
import Modal from '../../components/common/Modal';
import { useToast } from '../../components/common/Toast';

export const CustomerReviews = () => {
  const toast = useToast();
  const [completedBookings, setCompletedBookings] = useState([]);
  const [loading, setLoading] = useState(true);

  const [reviewModalOpen, setReviewModalOpen] = useState(false);
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

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

  useEffect(() => {
    fetchCompletedBookings();
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
    } catch (err) {
      toast.error(err.message || 'Failed to submit review.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-8">
      <div className="border-b border-slate-800 pb-6">
        <h1 className="text-3xl font-extrabold text-white tracking-tight">Rental Reviews</h1>
        <p className="text-sm text-slate-400">Share your experience and leave 5-star ratings for your completed trips.</p>
      </div>

      <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
        <h3 className="text-lg font-bold text-white flex items-center gap-2">
          <Star className="w-5 h-5 text-amber-400" />
          <span>Completed Rentals Eligible For Review</span>
        </h3>

        {loading ? (
          <div className="py-8 text-center text-slate-400 text-sm">Loading eligible rentals...</div>
        ) : completedBookings.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <MessageSquare className="w-12 h-12 text-slate-700 mx-auto" />
            <p className="text-sm font-semibold text-slate-300">No Completed Rentals Yet</p>
            <p className="text-xs text-slate-500 max-w-xs mx-auto">
              Once you complete a rental reservation, you can return here to leave ratings and feedback.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {completedBookings.map((b) => (
              <div key={b.id} className="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-4">
                <div className="flex justify-between items-start">
                  <div>
                    <span className="text-[10px] uppercase font-bold text-blue-400 font-mono">
                      Ref: {b.booking_reference}
                    </span>
                    <h4 className="text-base font-bold text-white">
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
              <label className="block text-xs font-semibold text-slate-300">Select Rating</label>
              <div className="flex justify-center py-2">
                <StarRating rating={rating} size="lg" interactive onChange={(r) => setRating(r)} />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1.5">Your Review / Comments</label>
              <textarea
                rows="4"
                placeholder="Describe your driving experience, vehicle cleanliness, staff service..."
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                className="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500"
              />
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="w-full py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-blue-600/25 disabled:opacity-50"
            >
              {submitting ? 'Submitting...' : 'Post Vehicle Review'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
};

export default CustomerReviews;
