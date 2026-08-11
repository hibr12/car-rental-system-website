import React, { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { Car, Mail, Lock, Eye, EyeOff, LogIn, AlertCircle } from 'lucide-react';
import { getPortalHome } from '../../utils/roles';
import useAuthStore from '../../store/authStore';
import { useToast } from '../../components/common/Toast';

export const LoginPage = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  const { login, isLoading } = useAuthStore();
  const navigate = useNavigate();
  const location = useLocation();
  const toast = useToast();

  const from = location.state?.from?.pathname || '/dashboard';

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMessage('');

    if (!email || !password) {
      setErrorMessage('Please enter both email and password.');
      return;
    }

    try {
      const response = await login({ email, password });
      toast.success('Login successful! Welcome back.');
      
      const userRole = response.data?.user?.role;
      const targetPath = getPortalHome(userRole);

      navigate(from !== '/dashboard' ? from : targetPath, { replace: true });
    } catch (err) {
      setErrorMessage(err.message || 'Invalid email or password credentials.');
    }
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8 bg-theme-card border border-theme p-8 sm:p-10 rounded-3xl shadow-2xl transition-colors duration-200">
        <div className="text-center space-y-2">
          <Link to="/" className="inline-flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
              <Car className="w-6 h-6 text-white" />
            </div>
          </Link>
          <h2 className="text-2xl sm:text-3xl font-extrabold text-theme-primary tracking-tight">
            Sign In To Your Account
          </h2>
          <p className="text-xs text-theme-muted">Enter your credentials to access your rental dashboard.</p>
        </div>

        {errorMessage && (
          <div className="bg-rose-500/10 border border-rose-500/30 p-4 rounded-2xl flex items-center gap-3 text-rose-300 text-xs">
            <AlertCircle className="w-4 h-4 text-rose-400 shrink-0" />
            <span>{errorMessage}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Email Address</label>
            <div className="relative">
              <Mail className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                required
                placeholder="customer@carrental.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
            </div>
          </div>

          <div>
            <div className="flex items-center justify-between mb-1.5">
              <label className="text-xs font-semibold text-theme-secondary">Password</label>
              <Link to="/forgot-password" className="text-xs text-blue-400 hover:underline">
                Forgot password?
              </Link>
            </div>
            <div className="relative">
              <Lock className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-10 py-3 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-primary p-1"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <LogIn className="w-4 h-4" />
            <span>{isLoading ? 'Signing in...' : 'Sign In'}</span>
          </button>
        </form>

        {/* Demo Credentials Info Box */}
        <div className="bg-theme-input p-4 rounded-2xl border border-theme text-[11px] space-y-1.5 text-theme-muted transition-colors duration-200">
          <p className="font-semibold text-theme-secondary uppercase tracking-wider text-[10px]">Sample Backend Roles for Testing:</p>
          <div className="grid grid-cols-2 gap-1 text-[10px] font-mono">
            <div>Customer: customer@carrental.com</div>
            <div>Admin: admin@carrental.com</div>
            <div>Fleet Manager: fleet@carrental.com</div>
            <div>Staff: staff@carrental.com</div>
          </div>
        </div>

        <div className="text-center text-xs text-theme-muted pt-2">
          Don't have an account?{' '}
          <Link to="/register" className="font-bold text-blue-400 hover:underline">
            Register Now
          </Link>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
