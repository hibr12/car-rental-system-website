import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  ArrowLeft,
  Car,
  Calendar,
  MapPin,
  CreditCard,
  CheckCircle2,
  AlertCircle,
  Loader2,
  XCircle,
  Star,
  CircleDot,
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';
import { useToast } from '../../components/common/Toast';

const customerStatusMessage = (booking) => {
  const status = booking.booking_status || booking.status;
  const paid = booking.payment_status === 'paid';

  if (status === 'pending_branch_approval') {
    return {
      title: 'Awaiting Branch Approval',
      detail: 'Your booking request has been submitted and is waiting for branch approval.',
    };
  }
  if (status === 'payment_required' || status === 'payment_processing') {
    return paid
      ? { title: 'Payment Processing', detail: 'Your payment is being verified.' }
      : { title: 'Payment Required', detail: 'Your booking has been approved. Please complete payment to confirm.' };
  }
  if (status === 'pending_payment' || status === 'pending') {
    return paid
      ? { title: 'Payment Verified', detail: 'Your payment has been verified.' }
      : { title: 'Awaiting Payment', detail: 'Complete payment to continue your booking.' };
  }
  if (status === 'pending_admin_approval') {
    return { title: 'Awaiting Final Confirmation', detail: 'Branch approved. Awaiting final confirmation.' };
  }
  if (status === 'confirmed') {
    return { title: 'Booking Confirmed', detail: 'Your booking is confirmed.' };
  }
  if (status === 'ready_for_pickup') {
    return { title: 'Ready for Pickup', detail: 'Your vehicle is ready for pickup.' };
  }
  if (status === 'active') {
    return { title: 'Rental Active', detail: 'Enjoy your rental.' };
  }
  if (status === 'return_pending') {
    return { title: 'Return In Progress', detail: 'Vehicle return is being processed.' };
  }
  if (status === 'completed') {
    return { title: 'Completed', detail: 'Your rental is complete. You can leave a review.' };
  }
  if (status === 'cancelled') {
    return { title: 'Cancelled', detail: booking.cancellation_reason || 'This booking was cancelled.' };
  }
  if (status === 'rejected') {
    return { title: 'Rejected', detail: booking.rejection_reason || 'This booking was rejected.' };
  }
  return { title: formatStatus(status), detail: '' };
};

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

  const vehicle = booking.vehicle || {};
  const statusMsg = customerStatusMessage(booking);
  const timeline = booking.timeline || [];
  const actions = booking.allowed_actions || [];
  const canPay = actions.includes('pay');
  const canCancel = actions.includes('cancel');
  const canReview = actions.includes('write_review') || (booking.status === 'completed' && !booking.has_review);
  const paymentStateMessage = (() => {
    if (booking.payment_status === 'paid') return 'Payment successful and verified.';
    if (booking.payment_status === 'cash_pending') return 'Cash payment is waiting for branch verification.';
    if (booking.payment_status === 'failed' || booking.payment_status === 'invalid') return 'Payment failed. Try payment again.';
    if (canPay) return 'Your booking has been approved. Complete payment to confirm your reservation.';
    if ((booking.booking_status || booking.status) === 'pending_branch_approval') return 'Payment will be available after branch approval.';
    return 'Payment status will update automatically when verification completes.';
  })();

  return (
    <div className="max-w-4xl mx-auto space-y-8">
      <button
        onClick={() => navigate('/dashboard/bookings')}
        className="inline-flex items-center gap-2 text-xs font-semibold text-theme-muted hover:text-blue-400 transition-colors"
      >
        <ArrowLeft className="w-4 h-4" />
        <span>Back to Bookings</span>
      </button>

      <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="space-y-1">
            <p className="text-xs uppercase font-extrabold tracking-wider text-blue-400">Booking Reference</p>
            <h1 className="text-2xl font-extrabold text-theme-primary tracking-tight font-mono">
              {booking.booking_reference}
            </h1>
            <p className="text-sm font-semibold text-theme-primary mt-2">{statusMsg.title}</p>
            {statusMsg.detail && <p className="text-xs text-theme-muted">{statusMsg.detail}</p>}
          </div>
          <div className="flex items-center gap-3">
            <span className={`px-3 py-1.5 text-xs font-bold rounded-lg border ${getStatusBadgeStyle(booking.booking_status || booking.status)}`}>
              {formatStatus(booking.booking_status || booking.status)}
            </span>
            <span className={`px-3 py-1.5 text-xs font-bold rounded-lg border ${getStatusBadgeStyle(booking.payment_status)}`}>
              {formatStatus(booking.payment_status)}
            </span>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 space-y-6">
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
                {booking.branch?.name && (
                  <p className="text-xs text-theme-muted">Branch: {booking.branch.name}</p>
                )}
              </div>
            </div>
          </div>

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
                <span className="font-bold text-theme-primary">{formatCurrency(booking.subtotal)}</span>
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

          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-4">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider">Payment</h3>
            <div className="bg-theme-secondary p-4 rounded-2xl border border-theme space-y-2">
              <div className="flex justify-between text-xs">
                <span className="text-theme-muted">Amount Due</span>
                <span className="font-bold text-theme-primary">{formatCurrency(booking.total_price)}</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-theme-muted">Status</span>
                <span className={`px-2 py-0.5 text-[10px] font-bold rounded border ${getStatusBadgeStyle(booking.payment_status)}`}>
                  {formatStatus(booking.payment_status)}
                </span>
              </div>
              <p className="text-xs text-theme-muted">{paymentStateMessage}</p>
            </div>
            {canPay && (
              <Link
                to={`/checkout?booking_id=${booking.id}`}
                className="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold"
              >
                <CreditCard className="w-4 h-4" />
                Pay Now
              </Link>
            )}
          </div>
        </div>

        <div className="space-y-6">
          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-4">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider">Booking Timeline</h3>
            <div className="space-y-0">
              {timeline.map((step, idx) => {
                const isDone = step.state === 'done' || step.state === 'skipped';
                const isCurrent = step.state === 'current';
                const isRejected = step.state === 'rejected';
                return (
                  <div key={step.key} className="flex items-start gap-3">
                    <div className="flex flex-col items-center">
                      <div
                        className={`w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${
                          isRejected
                            ? 'bg-rose-500/20 text-rose-400'
                            : isCurrent
                            ? 'bg-blue-600 text-white'
                            : isDone
                            ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                            : 'bg-theme-hover text-theme-muted border border-theme'
                        }`}
                      >
                        {isDone ? <CheckCircle2 className="w-4 h-4" /> : <CircleDot className="w-4 h-4" />}
                      </div>
                      {idx < timeline.length - 1 && (
                        <div className={`w-0.5 h-6 ${isDone ? 'bg-emerald-500/40' : 'bg-theme-hover'}`} />
                      )}
                    </div>
                    <div className="pb-6">
                      <p className={`text-xs font-bold ${isCurrent ? 'text-blue-400' : isDone ? 'text-emerald-400' : 'text-theme-muted'}`}>
                        {step.label}
                      </p>
                      {step.detail && <p className="text-[10px] text-theme-muted mt-0.5">{step.detail}</p>}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          <div className="bg-theme-card border border-theme p-6 rounded-3xl shadow-xl space-y-3">
            <h3 className="text-sm font-bold text-theme-primary uppercase tracking-wider">Actions</h3>

            {canPay && (
              <Link
                to={`/checkout?booking_id=${booking.id}`}
                className="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg transition-all"
              >
                <CreditCard className="w-4 h-4" />
                Pay Now
              </Link>
            )}

            {canCancel && (
              <button
                onClick={handleCancelBooking}
                disabled={cancelling}
                className="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-bold text-xs transition-all disabled:opacity-50"
              >
                <XCircle className="w-4 h-4" />
                {cancelling ? 'Cancelling...' : 'Cancel Booking'}
              </button>
            )}

            {canReview && (
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
