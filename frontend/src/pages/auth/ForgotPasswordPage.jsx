import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Mail, ArrowLeft, CheckCircle2, Loader2 } from 'lucide-react';
import { useToast } from '../../components/common/Toast';
import authApi from '../../api/authApi';

export const ForgotPasswordPage = () => {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);
  const [loading, setLoading] = useState(false);
  const toast = useToast();

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email) {
      toast.error('Please enter your email address.');
      return;
    }

    setLoading(true);
    try {
      await authApi.forgotPassword(email);
      setSubmitted(true);
      toast.success('Password reset link sent to your email.');
    } catch (err) {
      toast.error(err.message || 'Unable to send reset link. Email not found.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-6 bg-theme-card border border-theme p-8 sm:p-10 rounded-3xl shadow-2xl">
        <Link to="/login" className="inline-flex items-center gap-2 text-xs text-theme-muted hover:text-blue-400">
          <ArrowLeft className="w-3.5 h-3.5" />
          <span>Back to Sign In</span>
        </Link>

        <div className="space-y-2">
          <h2 className="text-2xl font-bold text-theme-primary">Reset Password</h2>
          <p className="text-xs text-theme-muted">
            Enter your email address and we'll send you instructions to reset your password.
          </p>
        </div>

        {submitted ? (
          <div className="bg-emerald-500/10 border border-emerald-500/30 p-6 rounded-2xl text-center space-y-3">
            <CheckCircle2 className="w-10 h-10 text-emerald-400 mx-auto" />
            <h3 className="text-base font-bold text-theme-primary">Check Your Inbox</h3>
            <p className="text-xs text-theme-secondary">
              We have dispatched a password reset link to <strong className="text-emerald-300">{email}</strong>.
            </p>
            <Link to="/login" className="inline-flex items-center gap-1 text-xs font-semibold text-blue-400 hover:underline mt-4">
              <ArrowLeft className="w-3.5 h-3.5" />
              <span>Back to Sign In</span>
            </Link>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Email Address</label>
              <div className="relative">
                <Mail className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="email"
                  required
                  placeholder="yourname@example.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full bg-theme-secondary border border-theme rounded-xl pl-10 pr-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
            >
              {loading ? <><Loader2 className="w-4 h-4 animate-spin" />Sending... </>
              : 'Send Reset Link'}
            </button>
          </form>
        )}
      </div>
    </div>
  );
};

export default ForgotPasswordPage;
