import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import {
  CreditCard,
  Wallet,
  ArrowLeft,
  Loader2,
  CheckCircle2,
  AlertCircle,
  ShieldCheck,
  Banknote,
  Globe,
  ExternalLink,
} from 'lucide-react';
import bookingApi from '../../api/bookingApi';
import paymentApi from '../../api/paymentApi';
import useAuthStore from '../../store/authStore';
import { formatCurrency, formatDate, getStatusBadgeStyle, formatStatus } from '../../utils/formatters';
import { useToast } from '../../components/common/Toast';

const PAYMENT_METHODS = [
  {
    id: 'cash',
    label: 'Cash',
    description: 'Pay at counter upon vehicle pickup',
    icon: Banknote,
    selectedBorder: 'border-emerald-500',
    selectedBg: 'bg-emerald-500/10',
    iconSelectedBg: 'bg-emerald-500/20',
    iconSelectedText: 'text-emerald-400',
    radioSelected: 'border-emerald-500 bg-emerald-500',
  },
  {
    id: 'online_payment',
    label: 'Chapa',
    description: 'Pay online via Chapa secure payment',
    icon: Globe,
    selectedBorder: 'border-blue-500',
    selectedBg: 'bg-blue-500/10',
    iconSelectedBg: 'bg-blue-500/20',
    iconSelectedText: 'text-blue-400',
    radioSelected: 'border-blue-500 bg-blue-500',
    isOnline: true,
  },
];

export const CheckoutPage = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const bookingId = searchParams.get('booking_id');
  const { user } = useAuthStore();
  const toast = useToast();

  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedMethod, setSelectedMethod] = useState('online_payment');
  const [processing, setProcessing] = useState(false);
  const [verifying, setVerifying] = useState(false);
  const [error, setError] = useState(null);
  const verifyStarted = useRef(false);

  // Never keep a stale "Processing..." button after reload/back-navigation
  useEffect(() => {
    setProcessing(false);
  }, [bookingId]);

  useEffect(() => {
    if (!bookingId) {
      navigate('/dashboard/bookings');
      return;
    }

    // If Chapa redirected back here with a tx_ref, go to dedicated status page
    const txFromUrl = searchParams.get('tx_ref') || searchParams.get('trx_ref');
    if (txFromUrl) {
      navigate(`/payments/status?booking_id=${bookingId}&tx_ref=${encodeURIComponent(txFromUrl)}`, { replace: true });
      return;
    }

    const fetchBooking = async () => {
      try {
        setLoading(true);
        const res = await bookingApi.getById(bookingId);
        const data = res.data?.booking || res.data;
        setBooking(data);
      } catch (err) {
        toast.error(err.message || 'Failed to load booking details.');
        navigate('/dashboard/bookings');
      } finally {
        setLoading(false);
      }
    };

    fetchBooking();
  }, [bookingId, navigate, searchParams, toast]);

  const syncPaymentStatus = useCallback(async () => {
    if (!bookingId) return null;
    try {
      setVerifying(true);
      setError(null);

      const pendingTx = sessionStorage.getItem('pending_payment_tx_ref');
      const pendingBooking = sessionStorage.getItem('pending_payment_booking_id');

      if (pendingTx && (!pendingBooking || String(pendingBooking) === String(bookingId))) {
        const res = await paymentApi.verify(pendingTx);
        const payment = res.data;
        if (payment?.status === 'paid') {
          sessionStorage.removeItem('pending_payment_tx_ref');
          sessionStorage.removeItem('pending_payment_booking_id');
          toast.success('Payment verified with Chapa!');
          const bookingRes = await bookingApi.getById(bookingId);
          setBooking(bookingRes.data?.booking || bookingRes.data);
          return 'paid';
        }
      }

      const statusRes = await paymentApi.getBookingPaymentStatus(bookingId, { verify: true });
      const paymentStatus = statusRes.data?.payment_status || statusRes.data?.booking_payment_status;
      const bookingRes = await bookingApi.getById(bookingId);
      setBooking(bookingRes.data?.booking || bookingRes.data);

      if (paymentStatus === 'paid') {
        sessionStorage.removeItem('pending_payment_tx_ref');
        sessionStorage.removeItem('pending_payment_booking_id');
        toast.success('Payment verified with Chapa!');
        return 'paid';
      }

      return paymentStatus || 'pending';
    } catch (err) {
      setError(err.message || 'Unable to verify payment status.');
      return 'error';
    } finally {
      setVerifying(false);
      setProcessing(false);
    }
  }, [bookingId, toast]);

  // After return from Chapa (or unpaid pending booking), auto-verify once
  useEffect(() => {
    if (!booking || verifyStarted.current || loading) return;

    const pendingTx = sessionStorage.getItem('pending_payment_tx_ref');
    const pendingBooking = sessionStorage.getItem('pending_payment_booking_id');
    const hasPendingForThisBooking =
      pendingTx && (!pendingBooking || String(pendingBooking) === String(bookingId));

    const needsVerify =
      hasPendingForThisBooking ||
      (booking.payment_status === 'pending' && booking.payment_status !== 'paid');

    if (needsVerify && booking.payment_status !== 'paid') {
      verifyStarted.current = true;
      // Prefer dedicated status page when we have tx_ref
      if (hasPendingForThisBooking) {
        navigate(
          `/payments/status?booking_id=${bookingId}&tx_ref=${encodeURIComponent(pendingTx)}`,
          { replace: true }
        );
        return;
      }
      syncPaymentStatus();
    }
  }, [booking, bookingId, loading, navigate, syncPaymentStatus]);

  const handlePayment = async () => {
    if (!booking || processing) return;

    if (selectedMethod === 'cash') {
      try {
        setProcessing(true);
        setError(null);
        await paymentApi.create({
          booking_id: booking.id,
          amount: booking.total_price,
          payment_method: 'cash',
        });
        toast.success('Cash payment selected. Payment will be confirmed by the branch after cash is received.');
        navigate('/dashboard/bookings');
      } catch (err) {
        const msg = err.message || 'Failed to record payment.';
        if (msg.includes('already been paid')) {
          toast.info('This booking has already been paid.');
          navigate('/dashboard/bookings');
        } else {
          toast.error(msg);
          setError(msg);
        }
      } finally {
        setProcessing(false);
      }
      return;
    }

    if (selectedMethod === 'online_payment') {
      try {
        setProcessing(true);
        setError(null);
        const res = await paymentApi.initialize({
          booking_id: booking.id,
        });

        const { checkout_url, tx_ref } = res.data || {};

        if (checkout_url) {
          sessionStorage.setItem('pending_payment_tx_ref', tx_ref);
          sessionStorage.setItem('pending_payment_booking_id', String(booking.id));
          // Use replace so back button does not return to a stuck processing checkout
          window.location.replace(checkout_url);
          return;
        } else {
          throw new Error('No checkout URL received from payment gateway.');
        }
      } catch (err) {
        const msg = err.message || 'Failed to initialize payment. Please try again.';
        if (msg.includes('already been paid')) {
          toast.info('This booking has already been paid.');
          navigate('/dashboard/bookings');
        } else if (msg.includes('already exists') && msg.includes('pending')) {
          toast.warning('A payment attempt is already in progress. Please wait or try again in a few minutes.');
          setError('A payment is already being processed. Please wait a moment and try again.');
        } else if (msg.includes('not eligible')) {
          toast.error('This booking is not eligible for payment.');
          navigate('/dashboard/bookings');
        } else if (msg.includes('not exist')) {
          toast.error('Booking not found.');
          navigate('/dashboard/bookings');
        } else {
          toast.error(msg);
          setError(msg);
        }
        setProcessing(false);
      }
    }
  };

  if (loading) {
    return (
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-6 animate-pulse">
        <div className="h-8 bg-theme-hover rounded w-1/3" />
        <div className="h-64 bg-theme-hover rounded-3xl" />
        <div className="h-48 bg-theme-hover rounded-3xl" />
      </div>
    );
  }

  if (!booking) {
    return (
      <div className="max-w-3xl mx-auto px-4 py-20 text-center space-y-4">
        <AlertCircle className="w-16 h-16 text-rose-400 mx-auto" />
        <h2 className="text-2xl font-bold text-theme-primary">Booking Not Found</h2>
        <button
          onClick={() => navigate('/dashboard/bookings')}
          className="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold text-sm"
        >
          Back to Bookings
        </button>
      </div>
    );
  }

  const isPaid = booking.payment_status === 'paid';
  const isCashPending = booking.payment_status === 'cash_pending';
  const isPaymentProcessing =
    !isPaid &&
    (verifying ||
      booking.payment_status === 'pending' ||
      sessionStorage.getItem('pending_payment_tx_ref'));
  const payableStatuses = [
    'payment_required',
    'payment_processing',
    'pending_payment',
    'pending',
    'payment_verified',
  ];
  const bookingStatus = booking.booking_status || booking.status;
  const canPay =
    !isPaid &&
    !isCashPending &&
    !isPaymentProcessing &&
    payableStatuses.includes(bookingStatus);

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
      {/* Back Button */}
      <button
        onClick={() => navigate(-1)}
        className="inline-flex items-center gap-2 text-xs font-semibold text-theme-muted hover:text-blue-400 transition-colors"
      >
        <ArrowLeft className="w-4 h-4" />
        <span>Back</span>
      </button>

      {/* Page Header */}
      <div className="border-b border-theme pb-6">
        <span className="text-xs uppercase font-extrabold tracking-wider text-blue-400">
          Secure Checkout
        </span>
        <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight mt-1">
          Complete Your Payment
        </h1>
        <p className="text-sm text-theme-muted mt-1">
          Review your booking details and choose a payment method.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left: Payment Method Selection */}
        <div className="lg:col-span-2 space-y-6">
          {/* Not payable in current state */}
          {!canPay && !isPaid && !isCashPending && !isPaymentProcessing && (
            <div className="bg-amber-50 border border-[#F59E0B]/30 rounded-2xl p-5 flex items-center gap-4">
              <AlertCircle className="w-8 h-8 text-[#F59E0B] shrink-0" />
              <div>
                <h3 className="font-bold text-[#F59E0B]">Payment Not Available</h3>
                <p className="text-xs text-[#64748B]">
                  This booking cannot accept payment in its current status ({formatStatus(bookingStatus)}).
                </p>
              </div>
            </div>
          )}

          {/* Legacy payment-before-approval row: paid but still awaiting approval */}
          {isPaid && ['pending_branch_approval', 'pending_admin_approval'].includes(bookingStatus) && (
            <div className="bg-blue-50 border border-[#2563EB]/30 rounded-2xl p-5 flex items-center gap-4">
              <ShieldCheck className="w-8 h-8 text-[#2563EB] shrink-0" />
              <div>
                <h3 className="font-bold text-[#2563EB]">Payment Verified</h3>
                <p className="text-xs text-[#64748B]">
                  Your payment has been verified. This legacy booking is awaiting approval review.
                </p>
              </div>
            </div>
          )}

          {/* Cash pending */}
          {isCashPending && (
            <div className="bg-amber-50 border border-[#F59E0B]/30 rounded-2xl p-5 flex items-center gap-4">
              <Banknote className="w-8 h-8 text-[#F59E0B] shrink-0" />
              <div>
                <h3 className="font-bold text-[#F59E0B]">Cash Payment Pending</h3>
                <p className="text-xs text-[#64748B]">
                  Waiting for branch payment confirmation after cash is received.
                </p>
              </div>
            </div>
          )}

          {/* Already Paid Banner */}
          {isPaid && (
            <div className="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-5 flex items-center gap-4">
              <CheckCircle2 className="w-8 h-8 text-emerald-400 shrink-0" />
              <div>
                <h3 className="font-bold text-emerald-400">Payment Complete</h3>
                <p className="text-xs text-emerald-300/80">
                  This payment was verified with Chapa and recorded successfully.
                </p>
              </div>
            </div>
          )}

          {/* Verifying / pending sync */}
          {isPaymentProcessing && !isPaid && (
            <div className="bg-blue-500/10 border border-blue-500/30 rounded-2xl p-5 space-y-3">
              <div className="flex items-center gap-3">
                {verifying ? (
                  <Loader2 className="w-6 h-6 text-blue-400 animate-spin shrink-0" />
                ) : (
                  <ShieldCheck className="w-6 h-6 text-blue-400 shrink-0" />
                )}
                <div>
                  <h3 className="font-bold text-blue-400">
                    {verifying ? 'Verifying payment with Chapa...' : 'Payment may still be processing'}
                  </h3>
                  <p className="text-xs text-theme-muted mt-0.5">
                    Status is confirmed only after the backend verifies the transaction with Chapa.
                  </p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => {
                  const pendingTx = sessionStorage.getItem('pending_payment_tx_ref');
                  if (pendingTx) {
                    navigate(
                      `/payments/status?booking_id=${bookingId}&tx_ref=${encodeURIComponent(pendingTx)}`
                    );
                  } else {
                    syncPaymentStatus();
                  }
                }}
                disabled={verifying}
                className="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-semibold"
              >
                {verifying ? 'Checking...' : 'Check Payment Status'}
              </button>
            </div>
          )}

          {/* Payment Method Selection */}
          {canPay && !verifying && (
            <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 transition-colors duration-200">
              <div className="flex items-center gap-2 pb-4 border-b border-theme">
                <CreditCard className="w-5 h-5 text-blue-400" />
                <h2 className="text-lg font-bold text-theme-primary">Select Payment Method</h2>
              </div>

              <div className="space-y-3">
                {PAYMENT_METHODS.map((method) => {
                  const Icon = method.icon;
                  const isSelected = selectedMethod === method.id;
                  return (
                    <button
                      key={method.id}
                      onClick={() => setSelectedMethod(method.id)}
                      className={`w-full p-4 rounded-2xl border-2 text-left transition-all duration-200 flex items-center gap-4 ${
                        isSelected
                          ? `${method.selectedBorder} ${method.selectedBg}`
                          : 'border-theme hover:border-theme-hover bg-theme-secondary'
                      }`}
                    >
                      <div
                        className={`w-12 h-12 rounded-xl flex items-center justify-center ${
                          isSelected
                            ? `${method.iconSelectedBg} ${method.iconSelectedText}`
                            : 'bg-theme-hover text-theme-muted'
                        }`}
                      >
                        <Icon className="w-6 h-6" />
                      </div>
                      <div className="flex-1">
                        <p className="font-bold text-theme-primary">{method.label}</p>
                        <p className="text-xs text-theme-muted">{method.description}</p>
                      </div>
                      <div
                        className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                          isSelected
                            ? method.radioSelected
                            : 'border-theme-muted'
                        }`}
                      >
                        {isSelected && <div className="w-2 h-2 rounded-full bg-white" />}
                      </div>
                    </button>
                  );
                })}
              </div>

              {selectedMethod === 'cash' && (
                <div className="bg-amber-50 border border-[#F59E0B]/30 rounded-xl p-4 text-xs text-[#334155] space-y-1">
                  <p className="font-semibold text-[#F59E0B] flex items-center gap-1.5">
                    <Banknote className="w-3.5 h-3.5" />
                    Pay at Branch
                  </p>
                  <p>
                    Cash payment selected. Payment will be confirmed by the branch after the cash is received.
                  </p>
                </div>
              )}

              {selectedMethod === 'online_payment' && (
                <div className="bg-blue-50 border border-[#2563EB]/20 rounded-xl p-4 text-xs text-[#334155] space-y-1">
                  <p className="font-semibold text-[#2563EB] flex items-center gap-1.5">
                    <ShieldCheck className="w-3.5 h-3.5" />
                    Secure Payment via Chapa
                  </p>
                  <p>
                    You will be redirected to Chapa's secure payment page to complete your transaction.
                    After payment, you will be returned to this application.
                  </p>
                </div>
              )}

              {error && (
                <div className="bg-rose-500/10 border border-rose-500/30 rounded-xl p-4 text-xs text-rose-300 space-y-2">
                  <p className="font-semibold">{error}</p>
                  <button
                    onClick={() => {
                      setError(null);
                      handlePayment();
                    }}
                    className="text-blue-400 hover:text-blue-300 underline text-xs"
                  >
                    Try Again
                  </button>
                </div>
              )}

              {/* Pay Button */}
              <button
                onClick={handlePayment}
                disabled={processing}
                className="w-full py-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-base transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {processing ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    <span>Processing...</span>
                  </>
                ) : selectedMethod === 'online_payment' ? (
                  <>
                    <ExternalLink className="w-5 h-5" />
                    <span>Pay {formatCurrency(booking.total_price)} with Chapa</span>
                  </>
                ) : (
                  <>
                    <Banknote className="w-5 h-5" />
                    <span>Confirm Cash Payment</span>
                  </>
                )}
              </button>
            </div>
          )}
        </div>

        {/* Right: Booking Summary */}
        <div className="lg:col-span-1">
          <div className="bg-theme-card border border-theme rounded-3xl p-6 space-y-5 sticky top-28 transition-colors duration-200">
            <div className="pb-4 border-b border-theme">
              <h3 className="text-sm font-bold text-theme-primary">Booking Summary</h3>
              <p className="text-xs text-theme-muted font-mono mt-1">{booking.booking_reference}</p>
            </div>

            <div className="space-y-3 text-xs">
              <div className="flex justify-between">
                <span className="text-theme-muted">Vehicle</span>
                <span className="font-semibold text-theme-primary">
                  {booking.vehicle
                    ? `${booking.vehicle.brand} ${booking.vehicle.model}`
                    : `Vehicle #${booking.vehicle_id}`}
                </span>
              </div>

              <div className="flex justify-between">
                <span className="text-theme-muted">Pickup</span>
                <span className="font-semibold text-theme-primary">{formatDate(booking.pickup_date)}</span>
              </div>

              <div className="flex justify-between">
                <span className="text-theme-muted">Return</span>
                <span className="font-semibold text-theme-primary">{formatDate(booking.return_date)}</span>
              </div>

              <div className="flex justify-between">
                <span className="text-theme-muted">Duration</span>
                <span className="font-semibold text-theme-primary">{booking.number_of_days} days</span>
              </div>

              <div className="flex justify-between">
                <span className="text-theme-muted">Price/Day</span>
                <span className="font-semibold text-theme-primary">
                  {formatCurrency(booking.price_per_day)}
                </span>
              </div>
            </div>

            <div className="pt-3 border-t border-theme space-y-2">
              <div className="flex justify-between text-xs">
                <span className="text-theme-muted">Subtotal</span>
                <span className="font-semibold text-theme-secondary">{formatCurrency(booking.subtotal)}</span>
              </div>
              {booking.additional_charges > 0 && (
                <div className="flex justify-between text-xs">
                  <span className="text-theme-muted">Additional Charges</span>
                  <span className="font-semibold text-theme-secondary">
                    {formatCurrency(booking.additional_charges)}
                  </span>
                </div>
              )}
              {booking.discount > 0 && (
                <div className="flex justify-between text-xs">
                  <span className="text-theme-muted">Discount</span>
                  <span className="font-semibold text-emerald-400">
                    -{formatCurrency(booking.discount)}
                  </span>
                </div>
              )}
              <div className="flex justify-between items-center pt-2 border-t border-theme">
                <span className="font-bold text-theme-primary text-sm">Total Amount</span>
                <span className="font-extrabold text-xl text-blue-400">
                  {formatCurrency(booking.total_price)}
                </span>
              </div>
            </div>

            {/* Payment Status */}
            <div className="pt-3 border-t border-theme">
              <div className="flex justify-between items-center text-xs">
                <span className="text-theme-muted">Payment Status</span>
                <span
                  className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(
                    booking.payment_status
                  )}`}
                >
                  {formatStatus(booking.payment_status)}
                </span>
              </div>
              <div className="flex justify-between items-center text-xs mt-2">
                <span className="text-theme-muted">Booking Status</span>
                <span
                  className={`px-2.5 py-1 text-[11px] font-bold rounded-lg border ${getStatusBadgeStyle(
                    booking.status
                  )}`}
                >
                  {formatStatus(booking.status)}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CheckoutPage;
