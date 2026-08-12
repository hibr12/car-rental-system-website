import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import { Mail, Lock, Loader2, Eye, EyeOff, Car } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import { getPortalHome, roleMatchesPortal } from '../../utils/roles';

const PORTAL_LABELS = {
  admin:   'Admin Portal',
  manager: 'Branch Manager Portal',
  branch:  'Branch Manager Portal',
  fleet:   'Fleet Manager Portal',
  staff:   'Staff Portal',
};

export const ManagementLoginPage = ({ portal = 'admin' }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const { login, logout, isLoading, error, isAuthenticated, user, clearError } = useAuthStore();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [localError, setLocalError] = useState('');

  const portalHomes = {
    admin: '/admin',
    manager: '/manager',
    branch: '/branch',
    fleet: '/fleet',
    staff: '/staff',
  };
  const defaultHome = portalHomes[portal] || '/admin';
  const from = location.state?.from;
  const redirectTo =
    from && typeof from === 'string' && !from.includes('/login')
      ? from
      : defaultHome;

  // Only auto-enter the portal when the signed-in role matches THIS portal.
  // Never send customers (or wrong roles) to "/".
  useEffect(() => {
    if (isAuthenticated && user && roleMatchesPortal(user.role, portal)) {
      navigate(redirectTo || getPortalHome(user.role), { replace: true });
    }
  }, [isAuthenticated, user, portal, redirectTo, navigate]);

  useEffect(() => () => clearError(), [clearError]);

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

      if (!userData?.role) {
        setLocalError('Login succeeded but no role was returned.');
        return;
      }

      if (roleMatchesPortal(userData.role, portal)) {
        navigate(redirectTo || getPortalHome(userData.role), { replace: true });
        return;
      }

      // Wrong account for this portal — stay on sign-in, do not go to "/"
      await logout();
      setLocalError(
        `This account (${userData.role.replaceAll('_', ' ')}) cannot access the ${PORTAL_LABELS[portal]}. Sign in with a ${portal} account.`
      );
    } catch (err) {
      setLocalError(err.message || 'Login failed. Please check your credentials.');
    }
  };

  const displayError = localError || error;
  const portalLabel = PORTAL_LABELS[portal] || 'Management Portal';
  const wrongRoleSession = isAuthenticated && user && !roleMatchesPortal(user.role, portal);

  return (
    <div className="min-h-screen bg-white flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-md space-y-8">
        <div className="text-center space-y-3">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#2563EB] shadow-lg">
            <Car className="w-8 h-8 text-white" />
          </div>
          <div>
            <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Apex Rentals</h1>
            <p className="text-xs uppercase font-bold tracking-widest text-[#2563EB] mt-1">{portalLabel}</p>
          </div>
          <p className="text-sm text-[#64748B] max-w-xs mx-auto">
            Sign in to access your management dashboard.
          </p>
        </div>

        <div className="bg-white border border-[#E2E8F0] rounded-xl p-8 space-y-6 shadow-md">
          {wrongRoleSession && (
            <div className="bg-amber-50 border border-[#F59E0B]/30 rounded-xl p-3 text-center space-y-2">
              <p className="text-xs font-semibold text-[#F59E0B]">
                You are signed in as {user.role.replaceAll('_', ' ')}. Sign out to use a {portal} account.
              </p>
              <button
                type="button"
                onClick={() => logout()}
                className="text-xs font-bold text-[#2563EB] hover:underline"
              >
                Sign out current account
              </button>
            </div>
          )}

          {displayError && (
            <div className="bg-red-50 border border-[#DC2626]/20 rounded-xl p-3 text-center">
              <p className="text-xs font-semibold text-[#DC2626]">{displayError}</p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">Email Address</label>
              <div className="relative">
                <Mail className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin@carrental.com"
                  required
                  className="w-full bg-white border border-[#CBD5E1] rounded-xl pl-10 pr-4 py-3 text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:border-[#2563EB] transition-colors"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#64748B] uppercase mb-1.5">Password</label>
              <div className="relative">
                <Lock className="w-4 h-4 text-[#64748B] absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Enter your password"
                  required
                  className="w-full bg-white border border-[#CBD5E1] rounded-xl pl-10 pr-11 py-3 text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:border-[#2563EB] transition-colors"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#64748B] hover:text-[#0F172A] transition-colors"
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-3 rounded-xl bg-[#2563EB] hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2"
            >
              {isLoading ? (<><Loader2 className="w-4 h-4 animate-spin" />Signing in...</>) : 'Sign In'}
            </button>
          </form>

          <div className="bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl p-3 text-[10px] text-[#64748B] space-y-1">
            <p className="font-semibold text-[#475569] uppercase tracking-wider">Seeded test accounts (password: password)</p>
            {portal === 'fleet' ? (
              <p>Fleet manager: fleet@carrental.com</p>
            ) : portal === 'manager' || portal === 'branch' ? (
              <p>Branch manager: cmc.manager@apexrentals.com · bole.manager@apexrentals.com</p>
            ) : (
              <>
                <p>Admin: admin@carrental.com · Staff: staff@carrental.com</p>
                <p>Branch manager: cmc.manager@apexrentals.com</p>
              </>
            )}
          </div>
        </div>

        <div className="text-center">
          <p className="text-xs text-[#64748B]">
            Looking for the customer site?{' '}
            <Link to="/" className="text-[#2563EB] hover:text-blue-700 font-semibold underline">
              Go to homepage
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default ManagementLoginPage;
