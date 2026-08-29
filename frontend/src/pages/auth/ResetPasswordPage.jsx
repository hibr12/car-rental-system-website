import React, { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Lock, ArrowLeft, CheckCircle2, Loader2 } from 'lucide-react';
import { useToast } from '../../components/common/Toast';
import authApi from '../../api/authApi';

export const ResetPasswordPage = () => {
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const toast = useToast();
  const [searchParams] = useSearchParams();

  const token = searchParams.get('token');
  const email = searchParams.get('email');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (password !== confirmPassword) {
      toast.error('Passwords do not match.');
      return;
    }
    if (password.length < 8) {
      toast.error('Password must be at least 8 characters long.');
      return;
    }
    if (!token || !email) {
      toast.error('Invalid or expired reset link.');
      return;
    }

    setLoading(true);
    try {
      await authApi.resetPassword({ token, email, password, password_confirmation: confirmPassword });
      setSuccess(true);
      toast.success('Password updated successfully! Please sign in.');
    } catch (err) {
      toast.error(err.message || 'Invalid or expired reset token.');
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
          <h2 className="text-2xl font-bold text-theme-primary">Create New Password</h2>
          <p className="text-xs text-theme-muted">Your new password must be at least 8 characters long.</p>
        </div>

        {success ? (
          <div className="bg-emerald-500/10 border border-emerald-500/30 p-6 rounded-2xl text-center space-y-3">
            <CheckCircle2 className="w-10 h-10 text-emerald-400 mx-auto" />
            <h3 className="text-base font-bold text-theme-primary">Password Updated</h3>
            <p className="text-xs text-theme-secondary">
              Your password has been successfully reset.
            </p>
            <Link to="/login" className="inline-flex items-center gap-1 text-xs font-semibold text-blue-400 hover:underline mt-4">
              <ArrowLeft className="w-3.5 h-3.5" />
              <span>Sign In</span>
            </Link>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <input type="hidden" name="token" value={token} />
            <input type="hidden" name="email" value={email} />
            <div>
              <label className="block text-xs font-semibold text-theme-secondary mb-1.5">New Password</label>
              <div className="relative">
                <Lock className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="password"
                  required
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full bg-theme-secondary border border-theme rounded-xl pl-10 pr-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Confirm New Password</label>
              <div className="relative">
                <Lock className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="password"
                  required
                  placeholder="••••••••"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  className="w-full bg-theme-secondary border border-theme rounded-xl pl-10 pr-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
            >
              {loading ? <><Loader2 className="w-4 h-4 animate-spin" />Updating... </>
              : 'Update Password'}
            </button>
          </form>
        )}
      </div>
    </div>
  );
};

export default ResetPasswordPage;
