import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import {
  CheckCircle2,
  XCircle,
  Clock,
  Loader2,
  AlertTriangle,
  ArrowRight,
  RefreshCw,
  ShieldCheck,
} from 'lucide-react';
import paymentApi from '../../api/paymentApi';
import { formatCurrency } from '../../utils/formatters';
import { useToast } from '../../components/common/Toast';

// Poll schedule: immediate, then 3s intervals up to ~30s total
const POLL_DELAYS_MS = [0, 3000, 3000, 3000, 3000, 3000, 3000, 3000, 3000, 3000];

export const PaymentStatusPage = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const toast = useToast();

  const [status, setStatus] = useState('checking'); // checking | success | pending | failed | error
  const [paymentData, setPaymentData] = useState(null);
  const [bookingMeta, setBookingMeta] = useState(null);
  const [errorMessage, setErrorMessage] = useState('');
  const pollIndex = useRef(0);
  const timerRef = useRef(null);
  const started = useRef(false);
  const pollingActive = useRef(false);

  const resolveTxRef = useCallback(() => {
    return (
      searchParams.get('tx_ref') ||
      searchParams.get('trx_ref') ||
      sessionStorage.getItem('pending_payment_tx_ref')
    );
  }, [searchParams]);

  const resolveBookingId = useCallback(() => {
    return (
      searchParams.get('booking_id') ||
      sessionStorage.getItem('pending_payment_booking_id')
    );
  }, [searchParams]);

  const applyPaymentResult = useCallback((payment, meta = null) => {
    if (payment) setPaymentData(payment);
    if (meta) setBookingMeta(meta);

    const paymentStatus = payment?.status;
    const bookingPaymentStatus = meta?.booking_payment_status;

    if (paymentStatus === 'paid' || bookingPaymentStatus === 'paid') {
      setStatus('success');
      sessionStorage.removeItem('pending_payment_tx_ref');
      sessionStorage.removeItem('pending_payment_booking_id');
      toast.success('Payment confirmed successfully!');
      return 'paid';
    }

    if (paymentStatus === 'failed' || paymentStatus === 'cancelled') {
      setStatus('failed');
      setErrorMessage(payment?.failure_reason || 'Payment could not be confirmed with Chapa.');
      return 'failed';
    }

    setStatus('pending');
    setErrorMessage('Your payment is still being verified with Chapa.');
    return 'pending';
  }, [toast]);

  const checkStatusOnce = useCallback(async () => {
    const txRef = resolveTxRef();
    const bookingId = resolveBookingId();

    if (!txRef && !bookingId) {
      setStatus('error');
      setErrorMessage('No payment reference found. Please check your booking status.');
      return 'error';
    }

    setStatus('checking');

    try {
      if (txRef) {
        const res = await paymentApi.verify(txRef);
        const payment = res.data;
        const meta = {
          booking_payment_status: payment?.booking?.payment_status,
          booking_status: payment?.booking?.status,
        };

        if (res.retryable && payment?.status !== 'paid') {
          applyPaymentResult(payment, meta);
          return 'pending';
        }

        return applyPaymentResult(payment, meta);
      }

      const res = await paymentApi.getBookingPaymentStatus(bookingId, { verify: true });
      const payment = res.data?.payment || res.data;
      const meta = {
        booking_payment_status: res.data?.booking_payment_status,
        booking_status: res.data?.booking_status,
      };

      if (res.data?.payment_status === 'paid' || payment?.status === 'paid') {
        return applyPaymentResult(payment || { status: 'paid', ...res.data }, meta);
      }

      return applyPaymentResult(payment || { status: res.data?.payment_status || 'pending' }, meta);
    } catch (err) {
      // Retryable network/server issues — keep polling, do not show false failure
      if (err.status === 0 || err.status >= 500) {
        setStatus('pending');
        setErrorMessage('Unable to reach the server. Still checking payment status…');
        return 'pending';
      }

      setStatus('failed');
      setErrorMessage(err.message || 'Unable to verify payment. Please try again.');
      return 'error';
    }
  }, [resolveTxRef, resolveBookingId, applyPaymentResult]);

  const scheduleNextPoll = useCallback(() => {
    if (!pollingActive.current) return;

    const delay = POLL_DELAYS_MS[Math.min(pollIndex.current, POLL_DELAYS_MS.length - 1)];
    pollIndex.current += 1;

    timerRef.current = setTimeout(async () => {
      const result = await checkStatusOnce();

      if (result === 'paid' || result === 'failed' || result === 'error') {
        pollingActive.current = false;
        return;
      }

      if (pollIndex.current >= POLL_DELAYS_MS.length) {
        pollingActive.current = false;
        setStatus('pending');
        setErrorMessage(
          'Your payment is still being verified. You can check your booking/payment history shortly, or tap Check Payment Status.'
        );
        return;
      }

      scheduleNextPoll();
    }, delay);
  }, [checkStatusOnce]);

  const startPolling = useCallback(async () => {
    if (timerRef.current) clearTimeout(timerRef.current);
    pollIndex.current = 0;
    pollingActive.current = true;

    const first = await checkStatusOnce();
    if (first === 'paid' || first === 'failed' || first === 'error') {
      pollingActive.current = false;
      return;
    }

    scheduleNextPoll();
  }, [checkStatusOnce, scheduleNextPoll]);

  useEffect(() => {
    if (started.current) return;
    started.current = true;
    startPolling();

    return () => {
      pollingActive.current = false;
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [startPolling]);

  const handleRetry = () => {
    pollingActive.current = false;
    if (timerRef.current) clearTimeout(timerRef.current);
    started.current = false;
    startPolling();
  };

  const bookingId = resolveBookingId() || paymentData?.booking_id;

  return (
    <div className="max-w-xl mx-auto px-4 py-16 text-center space-y-8">
      {status === 'checking' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-blue-500/10 text-blue-400 flex items-center justify-center mx-auto border border-blue-500/30">
            <Loader2 className="w-10 h-10 animate-spin" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-theme-primary">Checking Payment Status</h2>
            <p className="text-sm text-theme-muted">
              Confirming your transaction with Chapa. Do not close this page.
            </p>
          </div>
        </div>
      )}

      {status === 'pending' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center mx-auto border border-amber-500/30">
            <Clock className="w-10 h-10" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-theme-primary">Payment Still Processing</h2>
            <p className="text-sm text-theme-muted max-w-sm mx-auto">
              {errorMessage || 'We are still checking the payment status with Chapa.'}
            </p>
          </div>
          <button
            onClick={handleRetry}
            className="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm inline-flex items-center gap-2"
          >
            <RefreshCw className="w-4 h-4" />
            Check Payment Status
          </button>
        </div>
      )}

      {status === 'success' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/30">
            <CheckCircle2 className="w-10 h-10" />
          </div>
          <div className="space-y-2">
            <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400">
              Payment Successful
            </span>
            <h2 className="text-3xl font-bold text-theme-primary">Thank You!</h2>
            <p className="text-sm text-theme-muted max-w-sm mx-auto flex items-center justify-center gap-1.5">
              <ShieldCheck className="w-4 h-4 text-emerald-400" />
              Verified with Chapa and recorded in Apex Rentals.
            </p>
          </div>

          {paymentData && (
            <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-3 text-left text-sm">
              <div className="flex justify-between">
                <span className="text-theme-muted">Transaction Reference</span>
                <span className="font-mono font-bold text-blue-400 text-xs">
                  {paymentData.transaction_reference}
                </span>
              </div>
              {paymentData.gateway_reference && (
                <div className="flex justify-between">
                  <span className="text-theme-muted">Chapa Reference</span>
                  <span className="font-mono text-xs text-theme-primary">{paymentData.gateway_reference}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span className="text-theme-muted">Amount Paid</span>
                <span className="font-bold text-emerald-400">
                  {formatCurrency(paymentData.amount)}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-theme-muted">Payment Status</span>
                <span className="font-bold text-emerald-400 uppercase">Paid</span>
              </div>
              <div className="flex justify-between">
                <span className="text-theme-muted">Verification</span>
                <span className="font-bold text-emerald-400 uppercase">
                  {paymentData.verification_status === 'manually_confirmed' ? 'Manually Confirmed' : 'Verified'}
                </span>
              </div>
              {bookingMeta?.booking_status && (
                <div className="flex justify-between pt-2 border-t border-theme">
                  <span className="text-theme-muted">Booking Status</span>
                  <span className="font-semibold text-theme-primary capitalize">
                    {bookingMeta.booking_status.replace('_', ' ')}
                  </span>
                </div>
              )}
            </div>
          )}

          <div className="flex flex-col sm:flex-row gap-3 justify-center pt-2">
            <button
              onClick={() => navigate('/dashboard/bookings')}
              className="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm inline-flex items-center justify-center gap-2"
            >
              View My Bookings
              <ArrowRight className="w-4 h-4" />
            </button>
            {bookingId && (
              <button
                onClick={() => navigate(`/checkout?booking_id=${bookingId}`)}
                className="px-6 py-3 rounded-xl border border-theme text-theme-secondary hover:text-theme-primary font-semibold text-sm"
              >
                View Checkout
              </button>
            )}
          </div>
        </div>
      )}

      {status === 'failed' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-rose-500/10 text-rose-400 flex items-center justify-center mx-auto border border-rose-500/30">
            <XCircle className="w-10 h-10" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-theme-primary">Payment Not Confirmed</h2>
            <p className="text-sm text-theme-muted max-w-sm mx-auto">
              {errorMessage || 'We could not confirm your payment with Chapa.'}
            </p>
          </div>
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <button
              onClick={handleRetry}
              className="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm inline-flex items-center justify-center gap-2"
            >
              <RefreshCw className="w-4 h-4" />
              Check Payment Status
            </button>
            {bookingId && (
              <button
                onClick={() => navigate(`/checkout?booking_id=${bookingId}`)}
                className="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm"
              >
                Try Again
              </button>
            )}
          </div>
        </div>
      )}

      {status === 'error' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center mx-auto border border-amber-500/30">
            <AlertTriangle className="w-10 h-10" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-theme-primary">Something Went Wrong</h2>
            <p className="text-sm text-theme-muted">{errorMessage}</p>
          </div>
          <button
            onClick={() => navigate('/dashboard/bookings')}
            className="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm"
          >
            View My Bookings
          </button>
        </div>
      )}
    </div>
  );
};

export default PaymentStatusPage;
