import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import {
  Car, Menu, X, User, LogOut, LayoutDashboard, Shield, Wrench,
  UserCheck, ChevronDown, Sun, Moon, Search, Settings
} from 'lucide-react';
import useAuthStore from '../../store/authStore';
import useThemeStore from '../../store/themeStore';
import NotificationBell from '../common/NotificationBell';

export const Navbar = ({ transparent = false }) => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [userDropdownOpen, setUserDropdownOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const { user, isAuthenticated, logout } = useAuthStore();
  const { theme, toggleTheme } = useThemeStore();
  const navigate = useNavigate();
  const location = useLocation();

  // Scroll listener — only active in transparent mode (homepage)
  useEffect(() => {
    if (!transparent) {
      setScrolled(false);
      return;
    }
    const onScroll = () => setScrolled(window.scrollY > 40);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    return () => window.removeEventListener('scroll', onScroll);
  }, [transparent]);

  const handleLogout = async () => {
    await logout();
    setUserDropdownOpen(false);
    navigate('/login');
  };

  const navLinks = [
    { name: 'Home', path: '/' },
    { name: 'Vehicles', path: '/vehicles' },
    { name: 'Contact Us', path: '/contact' },
  ];

  const getDashboardPath = () => {
    if (!user) return '/dashboard';
    if (user.role === 'admin' || user.role === 'super_admin') return '/admin';
    if (user.role === 'branch_manager') return '/manager';
    if (user.role === 'fleet_manager') return '/fleet';
    if (user.role === 'staff') return '/staff';
    return '/dashboard';
  };

  const getDashboardIcon = () => {
    switch (user?.role) {
      case 'admin':
        return <Shield className="w-4 h-4 text-purple-400" />;
      case 'fleet_manager':
        return <Wrench className="w-4 h-4 text-indigo-400" />;
      case 'staff':
        return <UserCheck className="w-4 h-4 text-cyan-400" />;
      default:
        return <LayoutDashboard className="w-4 h-4 text-blue-400" />;
    }
  };

  /* ─── COMPUTED CLASSES ─── */
  const headerBg = transparent
    ? scrolled
      ? 'bg-white shadow-md'
      : 'bg-transparent shadow-none'
    : 'bg-white/90 backdrop-blur-xl';

  const textColor = transparent
    ? scrolled
      ? 'text-slate-800'
      : 'text-white'
    : 'text-theme-primary';

  const linkColor = transparent
    ? scrolled
      ? 'text-slate-600 hover:text-blue-600'
      : 'text-white/80 hover:text-blue-300'
    : 'text-theme-secondary hover:text-blue-400';

  const linkActiveColor = transparent
    ? scrolled
      ? 'text-blue-600 font-semibold'
      : 'text-blue-300 font-semibold'
    : 'text-blue-400 font-semibold';

  return (
    <header className={`fixed top-0 left-0 w-full z-50 transition-all duration-300 ${headerBg}`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        {/* Brand Logo */}
        <Link to="/" className="flex items-center gap-3 group">
          <div className={`w-11 h-11 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform ${
            transparent && !scrolled
              ? 'bg-blue-600 shadow-lg shadow-blue-600/30'
              : 'bg-blue-600'
          }`}>
            <Car className="w-6 h-6 text-white" />
          </div>
          <div className="flex flex-col">
            <span className={`text-xl font-bold tracking-tight flex items-center gap-1 ${textColor}`}>
              Appex<span className="text-blue-400">Rentals</span>
            </span>
            <span className={`text-[10px] uppercase tracking-wider font-semibold ${
              transparent && !scrolled
                ? 'text-white/60'
                : transparent && scrolled
                  ? 'text-slate-400'
                  : 'text-theme-muted'
            }`}>
              Drive Your Journey
            </span>
          </div>
        </Link>

        {/* Desktop Navigation Links */}
        <nav className="hidden md:flex items-center gap-8">
          {navLinks.map((link) => {
            const isActive = location.pathname === link.path;
            return (
              <Link
                key={link.path}
                to={link.path}
                className={`text-sm font-medium transition-colors ${isActive ? linkActiveColor : linkColor}`}
              >
                {link.name}
              </Link>
            );
          })}
        </nav>

        {/* Right Action Items */}
        <div className="hidden md:flex items-center gap-3">
          {/* Search Icon */}
          <button
            onClick={() => navigate('/vehicles')}
            className={`w-10 h-10 rounded-full flex items-center justify-center transition-colors ${
              transparent && !scrolled
                ? 'bg-white/10 hover:bg-white/20 text-white backdrop-blur-sm'
                : transparent && scrolled
                  ? 'bg-gray-100 hover:bg-gray-200 text-slate-700'
                  : 'bg-theme-secondary border border-theme hover:border-theme-hover text-theme-secondary hover:text-theme-primary'
            }`}
            aria-label="Search vehicles"
          >
            <Search className="w-4 h-4" />
          </button>

          {/* Theme Toggle */}
          <button
            onClick={toggleTheme}
            className={`w-10 h-10 rounded-full flex items-center justify-center transition-colors ${
              transparent && !scrolled
                ? 'bg-white/10 hover:bg-white/20 text-white backdrop-blur-sm'
                : transparent && scrolled
                  ? 'bg-gray-100 hover:bg-gray-200 text-slate-700'
                  : 'bg-theme-secondary border border-theme hover:border-theme-hover text-theme-secondary hover:text-theme-primary'
            }`}
            aria-label="Toggle theme"
          >
            {theme === 'dark' ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
          </button>

          {/* Profile Icon / Dropdown */}
          {isAuthenticated ? (
            <div className="relative">
              <button
                onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                className={`flex items-center gap-3 p-1.5 pl-3 rounded-full transition-all ${
                  transparent && !scrolled
                    ? 'bg-white/10 hover:bg-white/20 text-white border border-white/10 backdrop-blur-sm'
                    : transparent && scrolled
                      ? 'bg-gray-100 hover:bg-gray-200 text-slate-700 border border-gray-200'
                      : 'bg-theme-secondary border border-theme hover:border-theme-hover text-theme-secondary hover:text-theme-primary'
                }`}
              >
                <div className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm border ${
                  transparent && !scrolled
                    ? 'bg-blue-500/40 text-white border-white/20'
                    : transparent && scrolled
                      ? 'bg-blue-100 text-blue-600 border-blue-200'
                      : 'bg-blue-600/30 text-blue-400 border-blue-500/30'
                }`}>
                  {user?.name?.[0]?.toUpperCase() || 'U'}
                </div>
                <span className="text-sm font-medium hidden lg:inline">{user?.name}</span>
                <ChevronDown className={`w-4 h-4 pr-1 ${
                  transparent && !scrolled
                    ? 'text-white/60'
                    : transparent && scrolled
                      ? 'text-slate-400'
                      : 'text-theme-muted'
                }`} />
              </button>

              {/* Dropdown Menu */}
              {userDropdownOpen && (
                <div
                  className="absolute right-0 mt-2 w-56 bg-theme-card border border-theme rounded-2xl shadow-2xl py-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                  onMouseLeave={() => setUserDropdownOpen(false)}
                >
                  <div className="px-4 py-2 border-b border-theme">
                    <p className="text-xs text-theme-muted">Signed in as</p>
                    <p className="text-sm font-semibold text-theme-primary truncate">{user?.email}</p>
                    <span className="inline-block mt-1 px-2 py-0.5 text-[10px] uppercase font-bold rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">
                      {user?.role?.replace('_', ' ')}
                    </span>
                  </div>

                  <Link
                    to={getDashboardPath()}
                    onClick={() => setUserDropdownOpen(false)}
                    className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-theme-secondary hover:bg-theme-hover hover:text-theme-primary transition-colors"
                  >
                    {getDashboardIcon()}
                    <span>Dashboard</span>
                  </Link>

                  {user?.role === 'customer' && (
                    <Link
                      to="/dashboard/profile"
                      onClick={() => setUserDropdownOpen(false)}
                      className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-theme-secondary hover:bg-theme-hover hover:text-theme-primary transition-colors"
                    >
                      <Settings className="w-4 h-4 text-theme-muted" />
                      <span>Profile Settings</span>
                    </Link>
                  )}

                  {user?.role === 'customer' && (
                    <Link
                      to="/dashboard/license"
                      onClick={() => setUserDropdownOpen(false)}
                      className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-theme-secondary hover:bg-theme-hover hover:text-theme-primary transition-colors"
                    >
                      <svg className="w-4 h-4 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0M9 14h6m-6 3h3" />
                      </svg>
                      <span>Driver's License</span>
                    </Link>
                  )}

                  <Link
                    to="/dashboard/bookings"
                    onClick={() => setUserDropdownOpen(false)}
                    className="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-blue-600 hover:bg-blue-100 rounded-xl transition-colors"
                  >
                    <Car className="w-4 h-4 text-blue-600" />
                    <span>My Bookings</span>
                  </Link>

                  <button
                    onClick={handleLogout}
                    className="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors border-t border-theme mt-1"
                  >
                    <LogOut className="w-4 h-4" />
                    <span>Sign Out</span>
                  </button>
                </div>
              )}
            </div>
          ) : (
            <div className="flex items-center gap-3">
              <Link
                to="/login"
                className={`px-4 py-2 text-sm font-medium transition-colors ${
                  transparent && !scrolled
                    ? 'text-white/80 hover:text-white'
                    : transparent && scrolled
                      ? 'text-slate-600 hover:text-blue-600'
                      : 'text-theme-secondary hover:text-theme-primary'
                }`}
              >
                Sign In
              </Link>
              <Link
                to="/register"
                className={`px-5 py-2.5 rounded-xl text-sm font-semibold transition-all active:scale-[0.98] ${
                  transparent && !scrolled
                    ? 'bg-blue-600 text-white hover:bg-blue-500 shadow-lg shadow-blue-600/30'
                    : transparent && scrolled
                      ? 'bg-blue-600 text-white hover:bg-blue-700'
                      : 'bg-blue-600 text-white hover:bg-blue-700'
                }`}
              >
                Register
              </Link>
            </div>
          )}

          {/* My Bookings CTA Button — visible when authenticated */}
          {isAuthenticated && (
            <Link
              to="/dashboard/bookings"
              className={`px-5 py-2.5 rounded-xl text-sm font-semibold transition-all active:scale-[0.98] ${
                transparent && !scrolled
                  ? 'bg-blue-600 text-white hover:bg-blue-500 shadow-lg shadow-blue-600/30'
                  : 'bg-blue-600 text-white hover:bg-blue-700'
              }`}
            >
              My Bookings
            </Link>
          )}
        </div>

        {/* Mobile Menu Trigger */}
        <div className="flex items-center gap-3 md:hidden">
          <button
            onClick={toggleTheme}
            className={`p-2.5 rounded-lg transition-colors ${
              transparent && !scrolled
                ? 'bg-white/10 text-white hover:bg-white/20'
                : transparent && scrolled
                  ? 'bg-gray-100 text-slate-700 hover:bg-gray-200'
                  : 'bg-theme-secondary border border-theme text-theme-secondary'
            }`}
            aria-label="Toggle theme"
          >
            {theme === 'dark' ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
          </button>
          <button
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className={`p-2.5 rounded-lg transition-colors ${
              transparent && !scrolled
                ? 'bg-white/10 text-white hover:bg-white/20'
                : transparent && scrolled
                  ? 'bg-gray-100 text-slate-700 hover:bg-gray-200'
                  : 'bg-theme-secondary border border-theme text-theme-secondary hover:text-theme-primary'
            }`}
            aria-label="Toggle Navigation"
          >
            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {/* Mobile Drawer Navigation */}
      {mobileMenuOpen && (
        <div className={`md:hidden border-b px-6 py-6 space-y-4 animate-in slide-in-from-top-5 duration-200 ${
          transparent && !scrolled
            ? 'bg-gray-900/95 backdrop-blur-xl border-white/10'
            : transparent && scrolled
              ? 'bg-white border-gray-200 shadow-lg'
              : 'bg-theme-primary border-theme'
        }`}>
          <nav className="flex flex-col gap-3">
            {navLinks.map((link) => (
              <Link
                key={link.path}
                to={link.path}
                onClick={() => setMobileMenuOpen(false)}
                className={`py-2 text-base font-medium transition-colors ${
                  location.pathname === link.path
                    ? 'text-blue-400 font-bold'
                    : transparent && !scrolled
                      ? 'text-white/80'
                      : transparent && scrolled
                        ? 'text-slate-600'
                        : 'text-theme-secondary'
                }`}
              >
                {link.name}
              </Link>
            ))}
          </nav>

          <div className={`pt-4 border-t ${transparent && !scrolled ? 'border-white/10' : transparent && scrolled ? 'border-gray-200' : 'border-theme'}`}>
            {isAuthenticated ? (
              <div className="space-y-3">
                <div className="flex items-center gap-3 py-2">
                  <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-base border ${
                    transparent && !scrolled
                      ? 'bg-blue-500/30 text-white border-white/20'
                      : transparent && scrolled
                        ? 'bg-blue-100 text-blue-600 border-blue-200'
                        : 'bg-blue-600/30 text-blue-400 border-blue-500/30'
                  }`}>
                    {user?.name?.[0]?.toUpperCase()}
                  </div>
                  <div>
                    <p className={`text-sm font-semibold ${transparent && !scrolled ? 'text-white' : transparent && scrolled ? 'text-slate-800' : 'text-theme-primary'}`}>{user?.name}</p>
                    <p className={`text-xs ${transparent && !scrolled ? 'text-white/50' : transparent && scrolled ? 'text-slate-400' : 'text-theme-muted'}`}>{user?.email}</p>
                  </div>
                </div>

                <Link
                  to={getDashboardPath()}
                  onClick={() => setMobileMenuOpen(false)}
                  className="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-blue-600 text-white font-medium"
                >
                  {getDashboardIcon()}
                  <span>Go to Dashboard</span>
                </Link>

                <Link
                  to="/dashboard/bookings"
                  onClick={() => setMobileMenuOpen(false)}
                  className="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-blue-600 text-white font-medium"
                >
                  <Car className="w-4 h-4" />
                  <span>My Bookings</span>
                </Link>

                <button
                  onClick={() => { handleLogout(); setMobileMenuOpen(false); }}
                  className={`w-full flex items-center justify-center gap-2 py-2.5 rounded-xl font-medium ${
                    transparent && !scrolled
                      ? 'border border-white/20 text-white/70 hover:bg-white/10'
                      : transparent && scrolled
                        ? 'border border-gray-200 text-slate-500 hover:bg-gray-50'
                        : 'border border-rose-500/30 text-rose-400 hover:bg-rose-500/10'
                  }`}
                >
                  <LogOut className="w-4 h-4" />
                  <span>Sign Out</span>
                </button>
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-3">
                <Link
                  to="/login"
                  onClick={() => setMobileMenuOpen(false)}
                  className={`w-full py-3 text-center rounded-xl font-medium ${
                    transparent && !scrolled
                      ? 'bg-white/10 text-white'
                      : transparent && scrolled
                        ? 'bg-gray-100 text-slate-700'
                        : 'bg-theme-secondary border border-theme text-theme-primary'
                  }`}
                >
                  Sign In
                </Link>
                <Link
                  to="/register"
                  onClick={() => setMobileMenuOpen(false)}
                  className="w-full py-3 text-center rounded-xl bg-blue-600 text-white font-semibold"
                >
                  Register
                </Link>
              </div>
            )}
          </div>
        </div>
      )}
    </header>
  );
};

export default Navbar;
