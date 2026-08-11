import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import {
  CheckCircle2,
  XCircle,
  Clock,
  Loader2,
  AlertTriangle,
  ArrowRight,
  RefreshCw,
} from 'lucide-react';
import paymentApi from '../../api/paymentApi';
import { useToast } from '../../components/common/Toast';

export const PaymentStatusPage = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const toast = useToast();

  const [status, setStatus] = useState('verifying'); // verifying, success, failed, error
  const [paymentData, setPaymentData] = useState(null);
  const [errorMessage, setErrorMessage] = useState('');
  const verifyAttempted = useRef(false);

  useEffect(() => {
    const txRef = searchParams.get('tx_ref') || sessionStorage.getItem('pending_payment_tx_ref');
    const bookingId = searchParams.get('booking_id') || sessionStorage.getItem('pending_payment_booking_id');

    if (!txRef) {
      setStatus('error');
      setErrorMessage('No payment reference found. Please check your booking status.');
      return;
    }

    if (verifyAttempted.current) return;
    verifyAttempted.current = true;

    const verifyPayment = async () => {
      try {
        setStatus('verifying');
        const res = await paymentApi.verify(txRef);
        const payment = res.data;

        setPaymentData(payment);

        if (payment.status === 'paid') {
          setStatus('success');
          toast.success('Payment confirmed successfully!');
        } else if (payment.status === 'failed') {
          setStatus('failed');
          toast.error('Payment could not be confirmed.');
        } else {
          setStatus('failed');
          setErrorMessage('Payment is still pending. Please wait or try verifying again.');
        }
      } catch (err) {
        setStatus('failed');
        setErrorMessage(err.message || 'Unable to verify payment. Please check your booking.');
      } finally {
        // Clean up session storage
        sessionStorage.removeItem('pending_payment_tx_ref');
        sessionStorage.removeItem('pending_payment_booking_id');
      }
    };

    verifyPayment();
  }, [searchParams]);

  const handleRetry = () => {
    const txRef = searchParams.get('tx_ref') || sessionStorage.getItem('pending_payment_tx_ref');
    if (txRef) {
      verifyAttempted.current = false;
      setStatus('verifying');
      paymentApi
        .verify(txRef)
        .then((res) => {
          const payment = res.data;
          setPaymentData(payment);
          if (payment.status === 'paid') {
            setStatus('success');
            toast.success('Payment confirmed successfully!');
          } else {
            setStatus('failed');
            setErrorMessage('Payment could not be confirmed. Please try again or contact support.');
          }
        })
        .catch((err) => {
          setStatus('failed');
          setErrorMessage(err.message || 'Verification failed.');
        });
    }
  };

  return (
    <div className="max-w-xl mx-auto px-4 py-16 text-center space-y-8">
      {/* Verifying State */}
      {status === 'verifying' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-blue-500/10 text-blue-400 flex items-center justify-center mx-auto border border-blue-500/30">
            <Loader2 className="w-10 h-10 animate-spin" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-theme-primary">Verifying Payment</h2>
            <p className="text-sm text-theme-muted">
              Please wait while we confirm your transaction with the payment gateway...
            </p>
          </div>
          <div className="bg-theme-card border border-theme rounded-2xl p-4 text-xs text-theme-muted">
            <Clock className="w-4 h-4 inline mr-1.5" />
            This usually takes just a few seconds. Do not close this page.
          </div>
        </div>
      )}

      {/* Success State */}
      {status === 'success' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/30 shadow-lg shadow-emerald-500/10">
            <CheckCircle2 className="w-10 h-10" />
          </div>
          <div className="space-y-2">
            <span className="text-xs uppercase font-extrabold tracking-wider text-emerald-400">
              Payment Successful
            </span>
            <h2 className="text-3xl font-bold text-theme-primary">Thank You!</h2>
            <p className="text-sm text-theme-muted max-w-sm mx-auto">
              Your payment has been confirmed and your booking is now secured.
            </p>
          </div>

          {/* Payment Details */}
          {paymentData && (
            <div className="bg-theme-card border border-theme rounded-2xl p-6 space-y-3 text-left text-sm transition-colors duration-200">
              <div className="flex justify-between">
                <span className="text-theme-muted">Transaction Reference</span>
                <span className="font-mono font-bold text-blue-400 text-xs">
                  {paymentData.transaction_reference}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-theme-muted">Amount Paid</span>
                <span className="font-bold text-emerald-400">
                  ETB {Number(paymentData.amount).toLocaleString()}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-theme-muted">Payment Method</span>
                <span className="font-semibold text-theme-primary capitalize">
                  {paymentData.payment_method?.replace('_', ' ')}
                </span>
              </div>
              {paymentData.paid_at && (
                <div className="flex justify-between">
                  <span className="text-theme-muted">Paid At</span>
                  <span className="font-semibold text-theme-primary">
                    {new Date(paymentData.paid_at).toLocaleString()}
                  </span>
                </div>
              )}
            </div>
          )}

          <div className="flex flex-col sm:flex-row gap-3 justify-center pt-2">
            <button
              onClick={() => navigate('/dashboard/bookings')}
              className="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm transition-colors shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2"
            >
              View My Bookings
              <ArrowRight className="w-4 h-4" />
            </button>
            <button
              onClick={() => navigate('/dashboard')}
              className="px-6 py-3 rounded-xl border border-theme text-theme-secondary hover:text-theme-primary font-semibold text-sm transition-colors"
            >
              Back to Dashboard
            </button>
          </div>
        </div>
      )}

      {/* Failed State */}
      {status === 'failed' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-rose-500/10 text-rose-400 flex items-center justify-center mx-auto border border-rose-500/30">
            <XCircle className="w-10 h-10" />
          </div>
          <div className="space-y-2">
            <span className="text-xs uppercase font-extrabold tracking-wider text-rose-400">
              Payment Not Confirmed
            </span>
            <h2 className="text-2xl font-bold text-theme-primary">Payment Issue</h2>
            <p className="text-sm text-theme-muted max-w-sm mx-auto">
              {errorMessage || 'We could not confirm your payment. This may be due to a temporary issue.'}
            </p>
          </div>

          <div className="bg-theme-card border border-theme rounded-2xl p-5 text-xs text-theme-muted space-y-3 transition-colors duration-200">
            <div className="flex items-start gap-2">
              <AlertTriangle className="w-4 h-4 text-amber-400 shrink-0 mt-0.5" />
              <p>
                If you completed payment but it shows as failed, please wait a few minutes and
                try verifying again. If the issue persists, contact support.
              </p>
            </div>
          </div>

          <div className="flex flex-col sm:flex-row gap-3 justify-center pt-2">
            <button
              onClick={handleRetry}
              className="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm transition-colors shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2"
            >
              <RefreshCw className="w-4 h-4" />
              Verify Again
            </button>
            {paymentData?.booking_id && (
              <button
                onClick={() =>
                  navigate(`/checkout?booking_id=${paymentData.booking_id}`)
                }
                className="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition-colors flex items-center justify-center gap-2"
              >
                Try Payment Again
              </button>
            )}
            <button
              onClick={() => navigate('/dashboard/bookings')}
              className="px-6 py-3 rounded-xl border border-theme text-theme-secondary hover:text-theme-primary font-semibold text-sm transition-colors"
            >
              View Bookings
            </button>
          </div>
        </div>
      )}

      {/* Error State */}
      {status === 'error' && (
        <div className="space-y-6">
          <div className="w-20 h-20 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center mx-auto border border-amber-500/30">
            <AlertTriangle className="w-10 h-10" />
          </div>
          <div className="space-y-2">
            <h2 className="text-2xl font-bold text-theme-primary">Something Went Wrong</h2>
            <p className="text-sm text-theme-muted max-w-sm mx-auto">
              {errorMessage || 'An unexpected error occurred.'}
            </p>
          </div>
          <button
            onClick={() => navigate('/dashboard/bookings')}
            className="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm transition-colors"
          >
            View My Bookings
          </button>
        </div>
      )}
    </div>
  );
};

export default PaymentStatusPage;
