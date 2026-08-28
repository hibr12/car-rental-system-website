import React, { useEffect, useState } from 'react';
import { Mail, Loader2, RefreshCw } from 'lucide-react';
import { useToast } from '../../components/common/Toast';
import { useAuthStore } from '../../store/authStore';
import authApi from '../../api/authApi';

export const EmailVerificationNoticePage = () => {
  const { user, logout } = useAuthStore();
  const [resending, setResending] = useState(false);
  const toast = useToast();

  useEffect(() => {
    if (!user) {
      logout();
    }
  }, [user, logout]);

  const handleResend = async () => {
    if (!user) return;
    setResending(true);
    try {
      await authApi.resendVerification();
      toast.success('Verification email sent.');
    } catch (err) {
      toast.error(err.message || 'Failed to send verification email.');
    } finally {
      setResending(false);
    }
  };

  if (!user) return null;

  return (
    <div className="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-6 bg-theme-card border border-theme p-8 sm:p-10 rounded-3xl shadow-2xl text-center">
        <div className="w-16 h-16 mx-auto rounded-full bg-amber-500/10 flex items-center justify-center">
          <Mail className="w-8 h-8 text-amber-500" />
        </div>

        <div className="space-y-2">
          <h2 className="text-2xl font-bold text-theme-primary">Verify Your Email</h2>
          <p className="text-xs text-theme-muted">
            A verification link has been sent to <strong className="text-theme-primary">{user.email}</strong>.
            Please check your inbox (and spam folder) and click the link to verify your account.
          </p>
        </div>

        <div className="bg-amber-500/10 border border-amber-500/30 p-4 rounded-2xl space-y-3 text-xs text-amber-300">
          <p>You cannot access your dashboard until your email is verified.</p>
          <p>Didn't receive the email? Click "Resend" below.</p>
        </div>

        <div className="space-y-3">
          <button
            onClick={handleResend}
            disabled={resending}
            className="w-full py-3.5 rounded-2xl bg-amber-500 hover:bg-amber-400 text-white font-bold text-sm shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
          >
            {resending ? (
              <><Loader2 className="w-4 h-4 animate-spin" />Resending... </>
            ) : (
              <><RefreshCw className="w-4 h-4" />Resend Verification Email</>
            )}
          </button>

          <button
            onClick={async () => { await logout(); }}
            className="w-full py-3.5 rounded-2xl bg-theme-secondary hover:bg-theme-tertiary text-theme-primary font-bold text-sm"
          >
            Sign Out
          </button>
        </div>

        <div className="text-xs text-theme-muted">
          <p>Verification links expire after 60 minutes.</p>
          <p>If you continue to have issues, please contact support.</p>
        </div>
      </div>
    </div>
  );
};

export default EmailVerificationNoticePage;