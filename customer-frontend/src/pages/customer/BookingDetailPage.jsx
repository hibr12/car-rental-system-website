import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  ArrowLeft,
  Car,
  Calendar,
  MapPin,
  CreditCard,
  CheckCircle2,
  FileText,
  AlertCircle,
  Loader2,
  XCircle,
  Star,
  CircleDot,
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { useToast } from '../../components/common/Toast';

const TIMELINE_STEPS = [
  { key: 'created', label: 'Created' },
  { key: 'payment', label: 'Payment' },
  { key: 'confirmed', label: 'Confirmed' },
  { key: 'pickup', label: 'Pickup' },
  { key: 'active', label: 'Active' },
  { key: 'returned', label: 'Returned' },
  { key: 'completed', label: 'Completed' },
];

const getTimelineIndex = (status) => {
  const map = { pending: 0, confirmed: 2, active: 4, completed: 6, cancelled: -1 };
  return map[status] ?? 0;
};

const getPaymentIndex = (paymentStatus) => (paymentStatus === 'paid' ? 1 : -1);

export const BookingDetailPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [cancelling, setCancelling] = useState(false);

  useEffect(() => {
    const fetchBooking = async () => {
      try {
        setLoading(true);
        setError(null);
        const res = await bookingApi.getById(id);
        setBooking(res.data);
      } catch (err) {
        setError(err.message || 'Failed to load booking details.');
      } finally {
        setLoading(false);
      }
    };
    fetchBooking();
  }, [id]);

  const handleCancelBooking = async () => {
    if (!booking) return;
    if (!window.confirm(`Are you sure you want to cancel booking ${booking.booking_reference}?`)) return;
    try {
      setCancelling(true);
      await bookingApi.cancel(booking.id);
      toast.success('Booking cancelled successfully.');
      const res = await bookingApi.getById(id);
      setBooking(res.data);
    } catch (err) {
      toast.error(err.message || 'Failed to cancel booking.');
    } finally {
      setCancelling(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="w-8 h-8 text-blue-400 animate-spin" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-2xl mx-auto py-12 text-center space-y-4">
        <AlertCircle className="w-12 h-12 text-rose-400 mx-auto" />
        <p className="text-sm font-semibold text-theme-secondary">{error}</p>
        <button
          onClick={() => navigate('/dashboard/bookings')}
          className="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold"
        >
          Back to Bookings
        </button>
      </div>
    );
  }

  if (!booking) return null;

  const timelineIdx = getTimelineIndex(booking.status);
  const paymentIdx = getPaymentIndex(booking.payment_status);
  const effectiveIdx = Math.max(timelineIdx, paymentIdx);

  const vehicle = booking.vehicle || {};

  return (
    <div className="max-w-4xl mx-auto space-y-8">
      {/* Back Button */}
      <button
        onClick={() => navigate('/dashboard/bookings')}
        className="inline-flex items-center gap-2 text-xs font-semibold text-theme-muted hover:text-blue-400 transition-colors"
      >
        <ArrowLeft className="w-4 h-4" />
        <span>Back to Bookings</span>
      </button>

      {/* Booking Reference Header */}
      <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="space-y-1">
            <p className="text-xs uppercase font-extrabold tracking-wider text-blue-400">Booking Reference</p>
            <h1 className="text-2xl font-extrabold text-theme-primary tracking-tight font-mono">
              {booking.booking_reference}
            </h1>
          </div>
          <div className="flex items-center gap-3">
            <span className={`px-3 py-1.5 text-xs font-bold rounded-lg border ${getStatusBadgeStyle(booking.status)}`}>
              {formatStatus(booking.status)}
            </span>
            <span className={`px-3 py-1.5 text-xs font-bold rounded-lg border ${getStatusBadgeStyle(booking.payment_status)}`}>
              {formatStatus(booking.payment_status)}
            </span>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left Column - Vehicle & Rental Info */}
        <div className="lg:col-span-2 space-y-6">
          {/* Vehicle Information */}
          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-4">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider flex items-center gap-2">
              <Car className="w-4 h-4 text-blue-400" />
              Vehicle Information
            </h3>
            <div className="flex items-center gap-4">
              {vehicle.image_url && (
                <img
                  src={vehicle.image_url}
                  alt={`${vehicle.brand} ${vehicle.model}`}
                  className="w-24 h-24 rounded-xl object-cover border border-theme"
                />
              )}
              <div className="space-y-1">
                <p className="text-lg font-bold text-theme-primary">
                  {vehicle.brand} {vehicle.model}
                </p>
                <p className="text-xs text-theme-muted">
                  {vehicle.year && `${vehicle.year} • `}{vehicle.category?.name || 'N/A'}
                </p>
              </div>
            </div>
          </div>

          {/* Rental Period */}
          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-4">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider flex items-center gap-2">
              <Calendar className="w-4 h-4 text-blue-400" />
              Rental Period
            </h3>
            <div className="grid grid-cols-3 gap-4 text-center">
              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme">
                <p className="text-[10px] uppercase font-bold text-theme-muted mb-1">Pickup</p>
                <p className="text-sm font-bold text-theme-primary">{formatDate(booking.pickup_date)}</p>
              </div>
              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme flex flex-col items-center justify-center">
                <p className="text-[10px] uppercase font-bold text-theme-muted mb-1">Duration</p>
                <p className="text-lg font-extrabold text-blue-400">{booking.number_of_days} Days</p>
              </div>
              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme">
                <p className="text-[10px] uppercase font-bold text-theme-muted mb-1">Return</p>
                <p className="text-sm font-bold text-theme-primary">{formatDate(booking.return_date)}</p>
              </div>
            </div>
          </div>

          {/* Locations */}
          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-4">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider flex items-center gap-2">
              <MapPin className="w-4 h-4 text-blue-400" />
              Locations
            </h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme">
                <p className="text-[10px] uppercase font-bold text-theme-muted mb-1">Pickup Location</p>
                <p className="text-sm font-semibold text-theme-primary">{booking.pickup_location}</p>
              </div>
              <div className="bg-theme-secondary p-4 rounded-2xl border border-theme">
                <p className="text-[10px] uppercase font-bold text-theme-muted mb-1">Return Location</p>
                <p className="text-sm font-semibold text-theme-primary">{booking.return_location}</p>
              </div>
            </div>
          </div>

          {/* Price Breakdown */}
          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-4">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider flex items-center gap-2">
              <CreditCard className="w-4 h-4 text-blue-400" />
              Price Breakdown
            </h3>
            <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-3">
              <div className="flex justify-between text-xs text-theme-secondary">
                <span>Price per Day</span>
                <span className="font-bold text-theme-primary">{formatCurrency(booking.price_per_day)}</span>
              </div>
              <div className="flex justify-between text-xs text-theme-secondary">
                <span>Duration</span>
                <span className="font-bold text-theme-primary">{booking.number_of_days} Days</span>
              </div>
              <div className="flex justify-between text-xs text-theme-secondary">
                <span>Subtotal</span>
                <span className="font-bold text-theme-primary">
                  {formatCurrency(booking.price_per_day * booking.number_of_days)}
                </span>
              </div>
              {booking.additional_charges > 0 && (
                <div className="flex justify-between text-xs text-theme-secondary">
                  <span>Additional Charges</span>
                  <span className="font-bold text-amber-400">{formatCurrency(booking.additional_charges)}</span>
                </div>
              )}
              {booking.discount > 0 && (
                <div className="flex justify-between text-xs text-theme-secondary">
                  <span>Discount</span>
                  <span className="font-bold text-emerald-400">-{formatCurrency(booking.discount)}</span>
                </div>
              )}
              <div className="flex justify-between text-sm pt-3 border-t border-theme">
                <span className="font-bold text-theme-primary">Total Price</span>
                <span className="font-extrabold text-emerald-400 text-base">
                  {formatCurrency(booking.total_price)}
                </span>
              </div>
            </div>
          </div>

          {/* Special Requests */}
          {booking.notes && (
            <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-4">
              <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider flex items-center gap-2">
                <FileText className="w-4 h-4 text-blue-400" />
                Special Requests / Notes
              </h3>
              <p className="text-sm text-theme-secondary bg-theme-secondary p-4 rounded-2xl border border-theme italic">
                {booking.notes}
              </p>
            </div>
          )}
        </div>

        {/* Right Column - Timeline & Actions */}
        <div className="space-y-6">
          {/* Timeline */}
          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-4">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider">Booking Timeline</h3>
            <div className="space-y-0">
              {TIMELINE_STEPS.map((step, idx) => {
                const isCompleted = idx <= effectiveIdx;
                const isCurrent = idx === effectiveIdx;
                return (
                  <div key={step.key} className="flex items-start gap-3">
                    <div className="flex flex-col items-center">
                      <div
                        className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 transition-colors ${
                          isCurrent
                            ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/25'
                            : isCompleted
                            ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                            : 'bg-theme-hover text-theme-muted border border-theme'
                        }`}
                      >
                        {isCompleted ? (
                          <CheckCircle2 className="w-4 h-4" />
                        ) : (
                          <CircleDot className="w-4 h-4" />
                        )}
                      </div>
                      {idx < TIMELINE_STEPS.length - 1 && (
                        <div
                          className={`w-0.5 h-6 ${
                            idx < effectiveIdx ? 'bg-emerald-500/40' : 'bg-theme-hover'
                          }`}
                        />
                      )}
                    </div>
                    <div className="pb-6">
                      <p
                        className={`text-xs font-bold ${
                          isCurrent
                            ? 'text-blue-400'
                            : isCompleted
                            ? 'text-emerald-400'
                            : 'text-theme-muted'
                        }`}
                      >
                        {step.label}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Action Buttons */}
          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-3">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider">Actions</h3>

            {booking.payment_status !== 'paid' && (
              <Link
                to={`/checkout?booking_id=${booking.id}`}
                className="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/25 transition-all"
              >
                <CreditCard className="w-4 h-4" />
                Pay Now
              </Link>
            )}

            {['pending', 'confirmed'].includes(booking.status) && (
              <button
                onClick={handleCancelBooking}
                disabled={cancelling}
                className="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-bold text-xs transition-all disabled:opacity-50"
              >
                <XCircle className="w-4 h-4" />
                {cancelling ? 'Cancelling...' : 'Cancel Booking'}
              </button>
            )}

            {booking.status === 'completed' && (
              <Link
                to="/dashboard/reviews"
                className="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 font-bold text-xs transition-all"
              >
                <Star className="w-4 h-4" />
                Write Review
              </Link>
            )}

            <Link
              to="/dashboard/bookings"
              className="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl border border-theme text-theme-secondary hover:bg-theme-hover font-bold text-xs transition-all"
            >
              <ArrowLeft className="w-4 h-4" />
              Back to Bookings
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BookingDetailPage;
