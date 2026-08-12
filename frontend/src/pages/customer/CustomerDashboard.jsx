import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Car, ArrowRight, CheckCircle2, Clock, CreditCard, AlertTriangle, Loader2 } from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import useAuthStore from '../../store/authStore';
import { formatCurrency, formatDate, formatStatus, getStatusBadgeStyle } from '../../utils/formatters';

export const CustomerDashboard = () => {
  const { user } = useAuthStore();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    bookingApi
      .getUserBookings({ per_page: 5 })
      .then((res) => {
        setBookings(res.data || []);
      })
      .catch((err) => console.error('Failed to load user bookings:', err))
      .finally(() => setLoading(false));
  }, []);

  const derived = useMemo(() => {
    const payBookings = bookings.filter((b) => (b.allowed_actions || []).includes('pay'));
    const processingBookings = bookings.filter((b) => b.status === 'payment_processing');

    const activeBookings = bookings.filter((b) => b.status === 'active' || b.status === 'confirmed' || b.status === 'ready_for_pickup');
    const completedBookings = bookings.filter((b) => b.status === 'completed');

    const upcoming = [...bookings].sort((a, b) => {
      // Payment-required should be first.
      const aPriority = (a.allowed_actions || []).includes('pay') ? 0 : a.status === 'payment_processing' ? 1 : 2;
      const bPriority = (b.allowed_actions || []).includes('pay') ? 0 : b.status === 'payment_processing' ? 1 : 2;
      if (aPriority !== bPriority) return aPriority - bPriority;
      return new Date(a.pickup_date || 0) - new Date(b.pickup_date || 0);
    });

    return {
      payBookings,
      processingBookings,
      activeBookings,
      completedBookings,
      upcoming,
      totalBookings: bookings.length,
    };
  }, [bookings]);

  const primaryPaymentBooking = derived.payBookings[0] || derived.processingBookings[0];

  return (
    <div className="space-y-8">
      {/* Welcome Banner */}
      <div className="bg-theme-card border border-theme p-8 rounded-3xl flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl transition-colors duration-200">
        <div className="space-y-2">
          <span className="text-xs uppercase font-extrabold tracking-wider text-blue-400">
            Customer Dashboard
          </span>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">
            Hello, {user?.name || 'Valued Client'}!
          </h1>
          <p className="text-sm text-theme-muted max-w-xl">
            Track active vehicle rentals, manage reservation dates, and review completed trips from your personal workstation.
          </p>
        </div>
        <Link
          to="/vehicles"
          className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg transition-all self-start md:self-auto"
        >
          <Car className="w-4 h-4" />
          <span>Book New Vehicle</span>
        </Link>
      </div>

      {/* ACTION REQUIRED alert */}
      {loading ? null : derived.payBookings.length > 0 ? (
        <div className="bg-theme-card border-amber-400/50 border p-6 rounded-3xl shadow-xl">
          <div className="flex items-start gap-3">
            <AlertTriangle className="w-6 h-6 text-amber-400 mt-0.5" />
            <div className="flex-1">
              <div className="text-xs uppercase font-extrabold tracking-wider text-amber-400">ACTION REQUIRED</div>
              <h2 className="text-lg font-extrabold text-theme-primary mt-1">
                Complete payment to confirm your reservation.
              </h2>
              <div className="mt-2 flex flex-wrap gap-3 items-center">
                <span className="px-2.5 py-1 text-[11px] font-bold rounded-lg border border-amber-500/30 text-amber-400 bg-amber-500/10">
                  Payment Required
                </span>
                {primaryPaymentBooking?.total_price != null && (
                  <span className="text-sm font-semibold text-theme-primary">{formatCurrency(primaryPaymentBooking.total_price)}</span>
                )}
              </div>
              <div className="mt-4 flex flex-col sm:flex-row gap-3">
                <Link
                  to={`/checkout?booking_id=${primaryPaymentBooking.id}`}
                  className="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg transition-all"
                >
                  <CreditCard className="w-4 h-4" />
                  Pay Now
                </Link>
                <Link
                  to={`/dashboard/bookings/${primaryPaymentBooking.id}`}
                  className="inline-flex items-center justify-center px-6 py-3 rounded-2xl border border-theme text-theme-muted hover:text-theme-primary text-sm font-semibold transition-all"
                >
                  View Booking
                </Link>
              </div>
            </div>
          </div>
        </div>
      ) : derived.processingBookings.length > 0 ? (
        <div className="bg-theme-card border-blue-400/40 border p-6 rounded-3xl shadow-xl">
          <div className="flex items-start gap-3">
            <Clock className="w-6 h-6 text-blue-400 mt-0.5" />
            <div className="flex-1">
              <div className="text-xs uppercase font-extrabold tracking-wider text-blue-400">PAYMENT PROCESSING</div>
              <h2 className="text-lg font-extrabold text-theme-primary mt-1">Your payment is being verified.</h2>
              <div className="mt-4">
                <Link
                  to={`/payments/status?booking_id=${primaryPaymentBooking.id}`}
                  className="inline-flex items-center justify-center px-6 py-3 rounded-2xl border border-theme text-theme-muted hover:text-theme-primary text-sm font-semibold transition-all"
                >
                  Check Payment Status
                  <ArrowRight className="w-4 h-4 ml-2" />
                </Link>
              </div>
            </div>
          </div>
        </div>
      ) : null}

      {/* MY UPCOMING BOOKINGS */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        <div className="flex items-center justify-between pb-4 border-b border-theme">
          <div>
            <h3 className="text-lg font-bold text-theme-primary">MY UPCOMING BOOKINGS</h3>
            <p className="text-xs text-theme-muted">Next reservations waiting for your action</p>
          </div>
          <Link
            to="/dashboard/bookings"
            className="text-xs font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-1"
          >
            <span>View All</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {loading ? (
          <div className="space-y-3">
            <div className="h-10 bg-theme-hover rounded-xl animate-pulse" />
            <div className="h-10 bg-theme-hover rounded-xl animate-pulse" />
            <div className="h-10 bg-theme-hover rounded-xl animate-pulse" />
          </div>
        ) : derived.upcoming.length === 0 ? (
          <div className="text-center py-12 space-y-3">
            <Car className="w-12 h-12 text-theme-muted mx-auto" />
            <p className="text-sm font-semibold text-theme-secondary">No Upcoming Bookings</p>
            <p className="text-xs text-theme-muted max-w-xs mx-auto">Browse vehicles to create your next reservation.</p>
            <Link to="/vehicles" className="inline-block px-5 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-semibold">
              Browse Vehicles
            </Link>
          </div>
        ) : (
          <div className="space-y-4">
            {derived.upcoming.map((booking) => {
              const canPay = (booking.allowed_actions || []).includes('pay');
              const isProcessing = booking.status === 'payment_processing';
              const paymentStatus = booking.payment_status;

              const paymentBadge = paymentStatus === 'paid'
                ? { text: 'Payment Paid', style: 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10' }
                : paymentStatus === 'failed'
                  ? { text: 'Payment Failed', style: 'text-red-400 border-red-500/30 bg-red-500/10' }
                  : paymentStatus === 'cash_pending'
                    ? { text: 'Cash Payment Awaiting Verification', style: 'text-amber-400 border-amber-500/30 bg-amber-500/10' }
                    : paymentStatus === 'pending'
                      ? { text: 'Payment Required', style: 'text-amber-400 border-amber-500/30 bg-amber-500/10' }
                      : { text: formatStatus(paymentStatus), style: getStatusBadgeStyle(booking.status) };

              return (
                <div key={booking.id} className="bg-theme-secondary border border-theme rounded-2xl p-4">
                  <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div className="space-y-2">
                      <div className="flex items-center gap-2 flex-wrap">
                        <span className="px-2.5 py-1 text-[11px] font-bold rounded-lg border text-theme-muted bg-theme-hover">
                          {booking.booking_reference}
                        </span>
                        <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${paymentBadge.style}`}>
                          {paymentBadge.text}
                        </span>
                        <span className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(booking.status)}`}>
                          {formatStatus(booking.status)}
                        </span>
                      </div>

                      <div className="text-theme-primary font-semibold">
                        {booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model}` : `Vehicle #${booking.vehicle_id}`}
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs text-theme-muted pt-1">
                        <div>
                          <div className="font-semibold text-theme-secondary uppercase">Branch</div>
                          <div className="font-medium text-theme-primary">{booking.branch?.name || '—'}</div>
                        </div>
                        <div>
                          <div className="font-semibold text-theme-secondary uppercase">Pickup</div>
                          <div className="font-medium text-theme-primary">{formatDate(booking.pickup_date)}</div>
                        </div>
                        <div>
                          <div className="font-semibold text-theme-secondary uppercase">Return</div>
                          <div className="font-medium text-theme-primary">{formatDate(booking.return_date)}</div>
                        </div>
                      </div>

                      <div className="pt-2 text-sm">
                        <span className="text-theme-muted">Total</span>
                        <span className="ml-2 font-extrabold text-blue-400">{formatCurrency(booking.total_price)}</span>
                      </div>
                    </div>

                    <div className="flex flex-col gap-2 sm:min-w-[240px]">
                      {canPay ? (
                        <Link
                          to={`/checkout?booking_id=${booking.id}`}
                          className="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg transition-all"
                        >
                          Pay Now
                        </Link>
                      ) : isProcessing ? (
                        <Link
                          to={`/payments/status?booking_id=${booking.id}`}
                          className="inline-flex items-center justify-center px-5 py-3 rounded-2xl border border-theme text-theme-muted hover:text-theme-primary text-sm font-semibold transition-all"
                        >
                          Check Payment Status
                        </Link>
                      ) : paymentStatus === 'failed' ? (
                        <Link
                          to={`/checkout?booking_id=${booking.id}`}
                          className="inline-flex items-center justify-center px-5 py-3 rounded-2xl border border-theme text-theme-muted hover:text-theme-primary text-sm font-semibold transition-all"
                        >
                          Try Payment Again
                        </Link>
                      ) : paymentStatus === 'cash_pending' ? (
                        <div className="px-4 py-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 text-amber-400 text-xs font-semibold">
                          Please pay at {booking.branch?.name || 'your branch'}. Booking will be confirmed after staff verifies cash.
                        </div>
                      ) : null}

                      <Link
                        to={`/dashboard/bookings/${booking.id}`}
                        className="inline-flex items-center justify-center px-5 py-3 rounded-2xl border border-theme text-theme-muted hover:text-theme-primary text-sm font-semibold transition-all"
                      >
                        View Booking
                      </Link>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
};

export default CustomerDashboard;
