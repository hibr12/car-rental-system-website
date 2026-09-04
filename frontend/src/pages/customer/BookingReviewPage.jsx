import React, { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, CheckCircle2, Loader2, AlertCircle, Star } from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import reviewApi from '../../api/reviewApi';
import StarRating from '../../components/common/StarRating';
import { formatDate } from '../../utils/formatters';
import { useToast } from '../../components/common/Toast';

const RATING_FIELDS = [
  { key: 'overall_rating', label: 'Overall Rating', required: true },
  { key: 'vehicle_rating', label: 'Vehicle Condition', required: true },
  { key: 'cleanliness_rating', label: 'Cleanliness', required: true },
  { key: 'staff_rating', label: 'Staff Service', required: true },
  { key: 'value_rating', label: 'Value for Money', required: true },
];

const MAX_COMMENT = 1000;

export const BookingReviewPage = () => {
  const { bookingId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();

  const [booking, setBooking] = useState(null);
  const [eligibility, setEligibility] = useState(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [validationErrors, setValidationErrors] = useState({});
  const [submitted, setSubmitted] = useState(false);

  const [ratings, setRatings] = useState({
    overall_rating: 0,
    vehicle_rating: 0,
    cleanliness_rating: 0,
    staff_rating: 0,
    value_rating: 0,
  });
  const [comment, setComment] = useState('');

  useEffect(() => {
    const load = async () => {
      try {
        setLoading(true);
        setError('');

        const [bookingRes, eligibilityRes] = await Promise.all([
          bookingApi.getById(bookingId),
          reviewApi.getEligibility(bookingId),
        ]);

        setBooking(bookingRes.data);
        setEligibility(eligibilityRes.data);
      } catch (err) {
        if (err.status === 403) {
          setError('You are not authorized to access this booking.');
        } else {
          setError(err.message || 'Unable to load booking details.');
        }
      } finally {
        setLoading(false);
      }
    };

    if (bookingId) load();
  }, [bookingId]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setValidationErrors({});

    if (!ratings.overall_rating) {
      setValidationErrors({ overall_rating: 'Please select an overall rating.' });
      return;
    }

    const missing = RATING_FIELDS.filter((f) => f.required && !ratings[f.key]);
    if (missing.length > 0) {
      const errs = {};
      missing.forEach((f) => { errs[f.key] = `Please rate ${f.label.toLowerCase()}.`; });
      setValidationErrors(errs);
      return;
    }

    try {
      setSubmitting(true);
      await reviewApi.createForBooking(bookingId, {
        ...ratings,
        comment: comment.trim() || undefined,
      });
      setSubmitted(true);
      toast.success('Thank you for your review!');
    } catch (err) {
      toast.error(err.message || 'Failed to submit review.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-[60vh] flex flex-col items-center justify-center bg-white text-[#64748B]">
        <Loader2 className="w-8 h-8 animate-spin mb-3 text-[#2563EB]" />
        <p className="text-sm">Loading review form...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-lg mx-auto py-16 px-4 text-center space-y-4 bg-white">
        <AlertCircle className="w-12 h-12 text-[#DC2626] mx-auto" />
        <p className="text-sm text-[#334155]">{error}</p>
        <Link to="/dashboard" className="inline-flex items-center gap-2 text-sm text-[#2563EB] font-semibold">
          <ArrowLeft className="w-4 h-4" /> Back to Dashboard
        </Link>
      </div>
    );
  }

  if (submitted) {
    return (
      <div className="max-w-lg mx-auto py-16 px-4 text-center space-y-4 bg-white">
        <CheckCircle2 className="w-14 h-14 text-emerald-500 mx-auto" />
        <h1 className="text-2xl font-bold text-[#0F172A]">Review Submitted</h1>
        <p className="text-sm text-[#64748B]">Thank you for sharing your experience with Apex Rentals.</p>
        <Link
          to="/dashboard"
          className="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#2563EB] text-white text-sm font-semibold"
        >
          Back to Dashboard
        </Link>
      </div>
    );
  }

  if (eligibility && !eligibility.eligible) {
    return (
      <div className="max-w-lg mx-auto py-16 px-4 text-center space-y-4 bg-white">
        <AlertCircle className="w-12 h-12 text-amber-500 mx-auto" />
        <h1 className="text-xl font-bold text-[#0F172A]">
          {eligibility.already_reviewed ? 'Already Reviewed' : 'Not Eligible'}
        </h1>
        <p className="text-sm text-[#64748B]">
          {eligibility.message ||
            'You can review this rental after the vehicle is returned and the rental is completed.'}
        </p>
        <Link to={`/dashboard/bookings/${bookingId}`} className="text-sm text-[#2563EB] font-semibold inline-flex items-center gap-1">
          <ArrowLeft className="w-4 h-4" /> View Booking
        </Link>
      </div>
    );
  }

  const vehicle = booking?.vehicle;
  const branch = booking?.branch;

  return (
    <div className="max-w-2xl mx-auto py-8 px-4 sm:px-6 bg-white min-h-screen">
      <Link
        to={`/dashboard/bookings/${bookingId}`}
        className="inline-flex items-center gap-2 text-xs font-semibold text-[#64748B] hover:text-[#2563EB] mb-6"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to Booking
      </Link>

      <div className="border border-[#E2E8F0] rounded-2xl overflow-hidden shadow-sm">
        <div className="bg-[#F8FAFC] border-b border-[#E2E8F0] px-6 py-5">
          <h1 className="text-2xl font-extrabold text-[#0F172A]">Rate Your Rental Experience</h1>
          <div className="mt-4 space-y-1 text-sm text-[#334155]">
            <p className="font-bold text-[#0F172A] text-base">
              {vehicle ? `${vehicle.brand} ${vehicle.model}` : 'Your Vehicle'}
            </p>
            <p>{branch?.name || '—'}</p>
            <p className="font-mono text-xs text-[#64748B]">Booking: {booking?.booking_reference}</p>
            <div className="flex flex-wrap gap-4 pt-2 text-xs">
              <span><strong>Pickup:</strong> {formatDate(booking?.pickup_date)}</span>
              <span><strong>Return:</strong> {formatDate(booking?.return_date)}</span>
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="px-6 py-6 space-y-6">
          {RATING_FIELDS.map(({ key, label, required }) => (
            <div key={key} className="space-y-2">
              <div className="flex items-center justify-between">
                <label className="text-sm font-semibold text-[#0F172A]">
                  {label}
                  {required && <span className="text-[#DC2626] ml-1">*</span>}
                </label>
                {ratings[key] > 0 && (
                  <span className="text-xs text-[#64748B]">{ratings[key]} / 5</span>
                )}
              </div>
              <StarRating
                rating={ratings[key]}
                size="lg"
                interactive
                onChange={(val) => setRatings((prev) => ({ ...prev, [key]: val }))}
                light
              />
              {validationErrors[key] && (
                <p className="text-xs text-[#DC2626]">{validationErrors[key]}</p>
              )}
            </div>
          ))}

          <div className="space-y-2 pt-2 border-t border-[#E2E8F0]">
            <label className="text-sm font-semibold text-[#0F172A]">Tell us about your experience</label>
            <textarea
              rows={5}
              maxLength={MAX_COMMENT}
              placeholder="Write your review..."
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              className="w-full border border-[#CBD5E1] rounded-xl p-3 text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:border-[#2563EB] resize-y"
            />
            <p className="text-xs text-[#64748B] text-right">{comment.length} / {MAX_COMMENT}</p>
          </div>

          <button
            type="submit"
            disabled={submitting}
            className="w-full py-3.5 rounded-xl bg-[#2563EB] hover:bg-[#1D4ED8] text-white font-bold text-sm transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
          >
            {submitting ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Submitting...
              </>
            ) : (
              <>
                <Star className="w-4 h-4" />
                Submit Review
              </>
            )}
          </button>
        </form>
      </div>
    </div>
  );
};

export default BookingReviewPage;
