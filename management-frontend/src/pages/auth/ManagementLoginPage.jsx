import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Mail, Lock, Loader2, Eye, EyeOff, Car } from 'lucide-react';
import useAuthStore from '../../store/authStore';

export const ManagementLoginPage = () => {
  const navigate = useNavigate();
  const { login, isLoading, error, isAuthenticated, user, clearError } = useAuthStore();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [localError, setLocalError] = useState('');

  useEffect(() => {
    if (isAuthenticated && user) {
      redirectByRole(user.role);
    }
  }, [isAuthenticated, user]);

  useEffect(() => {
    return () => clearError();
  }, []);

  const redirectByRole = (role) => {
    switch (role) {
      case 'admin':
        navigate('/admin', { replace: true });
        break;
      case 'fleet_manager':
        navigate('/fleet', { replace: true });
        break;
      case 'staff':
        navigate('/staff', { replace: true });
        break;
      default:
        navigate('/', { replace: true });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLocalError('');

    if (!email || !password) {
      setLocalError('Please enter both email and password.');
      return;
    }

    try {
      const response = await login({ email, password });
      const userData = response.data?.user || response.data;
      if (userData?.role) {
        redirectByRole(userData.role);
      }
    } catch (err) {
      setLocalError(err.message || 'Login failed. Please check your credentials.');
    }
  };

  const displayError = localError || error;

  return (
    <div className="min-h-screen bg-theme-primary flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-md space-y-8">
        {/* Branding */}
        <div className="text-center space-y-3">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 shadow-lg shadow-blue-600/30">
            <Car className="w-8 h-8 text-white" />
          </div>
          <div>
            <h1 className="text-2xl font-extrabold text-theme-primary tracking-tight">
              Apex Rentals
            </h1>
            <p className="text-xs uppercase font-bold tracking-widest text-blue-400 mt-1">
              Management Portal
            </p>
          </div>
          <p className="text-sm text-theme-muted max-w-xs mx-auto">
            Sign in to access the admin, fleet, and staff management dashboards.
          </p>
        </div>

        {/* Login Form */}
        <div className="bg-theme-card border border-theme rounded-3xl p-8 space-y-6 shadow-xl transition-colors duration-200">
          {displayError && (
            <div className="bg-rose-500/10 border border-rose-500/20 rounded-xl p-3 text-center">
              <p className="text-xs font-semibold text-rose-400">{displayError}</p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-xs font-semibold text-theme-muted uppercase mb-1.5">
                Email Address
              </label>
              <div className="relative">
                <Mail className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin@apexrentals.com"
                  required
                  className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-theme-muted uppercase mb-1.5">
                Password
              </label>
              <div className="relative">
                <Lock className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Enter your password"
                  required
                  className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-11 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3.5 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-primary transition-colors"
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-bold text-sm shadow-lg shadow-blue-600/25 transition-all flex items-center justify-center gap-2"
            >
              {isLoading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Signing in...
                </>
              ) : (
                'Sign In'
              )}
            </button>
          </form>
        </div>

        {/* Footer Link */}
        <div className="text-center">
          <p className="text-xs text-theme-muted">
            Looking for the customer site?{' '}
            <a
              href="https://apexrentals.com"
              target="_blank"
              rel="noopener noreferrer"
              className="text-blue-400 hover:text-blue-300 font-semibold underline transition-colors"
            >
              apexrentals.com
            </a>
          </p>
        </div>
      </div>
    </div>
  );
};

export default ManagementLoginPage;
