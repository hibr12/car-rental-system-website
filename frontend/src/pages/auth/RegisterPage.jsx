import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Car, User, Mail, Phone, Lock, Eye, EyeOff, UserPlus, AlertCircle } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import { useToast } from '../../components/common/Toast';

export const RegisterPage = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  });

  const [showPassword, setShowPassword] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});

  const { register, isLoading } = useAuthStore();
  const navigate = useNavigate();
  const toast = useToast();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMessage('');
    setFieldErrors({});

    if (formData.password !== formData.password_confirmation) {
      setErrorMessage('Password confirmation does not match.');
      return;
    }

    if (formData.password.length < 8) {
      setErrorMessage('Password must be at least 8 characters long.');
      return;
    }

    try {
      const response = await register(formData);
      toast.success('Registration successful! Welcome to ApexRentals.');

      const userRole = response.data?.user?.role;
      let targetPath = '/dashboard';
      if (userRole === 'admin') targetPath = '/admin';
      else if (userRole === 'fleet_manager') targetPath = '/fleet';
      else if (userRole === 'staff') targetPath = '/staff';

      navigate(targetPath, { replace: true });
    } catch (err) {
      if (err.errors) {
        setFieldErrors(err.errors);
      }
      setErrorMessage(err.message || 'Registration failed. Please check input values.');
    }
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8 bg-theme-card border border-theme p-8 sm:p-10 rounded-3xl shadow-2xl transition-colors duration-200">
        <div className="text-center space-y-2">
          <Link to="/" className="inline-flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
              <Car className="w-6 h-6 text-white" />
            </div>
          </Link>
          <h2 className="text-2xl sm:text-3xl font-extrabold text-theme-primary tracking-tight">
            Create Your Account
          </h2>
          <p className="text-xs text-theme-muted">Join ApexRentals for effortless vehicle booking.</p>
        </div>

        {errorMessage && (
          <div className="bg-rose-500/10 border border-rose-500/30 p-4 rounded-2xl flex items-center gap-3 text-rose-300 text-xs">
            <AlertCircle className="w-4 h-4 text-rose-400 shrink-0" />
            <span>{errorMessage}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1">Full Name *</label>
            <div className="relative">
              <User className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                required
                placeholder="Jane Doe"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
            </div>
            {fieldErrors.name && <p className="text-[11px] text-rose-400 mt-1">{fieldErrors.name[0]}</p>}
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1">Email Address *</label>
            <div className="relative">
              <Mail className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                required
                placeholder="jane@example.com"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
            </div>
            {fieldErrors.email && <p className="text-[11px] text-rose-400 mt-1">{fieldErrors.email[0]}</p>}
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1">Phone Number</label>
            <div className="relative">
              <Phone className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="tel"
                placeholder="+1 (555) 123-4567"
                value={formData.phone}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1">Password *</label>
            <div className="relative">
              <Lock className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                placeholder="Minimum 8 characters"
                value={formData.password}
                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-10 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-primary p-1"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
            {fieldErrors.password && <p className="text-[11px] text-rose-400 mt-1">{fieldErrors.password[0]}</p>}
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1">Confirm Password *</label>
            <div className="relative">
              <Lock className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                placeholder="Re-enter password"
                value={formData.password_confirmation}
                onChange={(e) => setFormData({ ...formData, password_confirmation: e.target.value })}
                className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-10 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-blue-600/25 disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <UserPlus className="w-4 h-4" />
            <span>{isLoading ? 'Creating Account...' : 'Register Account'}</span>
          </button>
        </form>

        <div className="text-center text-xs text-theme-muted pt-2">
          Already have an account?{' '}
          <Link to="/login" className="font-bold text-blue-400 hover:underline">
            Sign In
          </Link>
        </div>
      </div>
    </div>
  );
};

export default RegisterPage;
