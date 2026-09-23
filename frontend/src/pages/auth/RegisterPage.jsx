import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Car, User, Mail, Lock, Eye, EyeOff, UserPlus, Globe } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import { useToast } from '../../components/common/Toast';

// Country codes with dialing codes
const COUNTRY_CODES = [
  { name: 'Ethiopia', code: 'ET', dialCode: '+251' },
  { name: 'United States', code: 'US', dialCode: '+1' },
  { name: 'United Kingdom', code: 'GB', dialCode: '+44' },
  { name: 'Kenya', code: 'KE', dialCode: '+254' },
  { name: 'Uganda', code: 'UG', dialCode: '+256' },
  { name: 'Tanzania', code: 'TZ', dialCode: '+255' },
  { name: 'Rwanda', code: 'RW', dialCode: '+250' },
  { name: 'South Africa', code: 'ZA', dialCode: '+27' },
  { name: 'Nigeria', code: 'NG', dialCode: '+234' },
  { name: 'Ghana', code: 'GH', dialCode: '+233' },
  { name: 'Egypt', code: 'EG', dialCode: '+20' },
  { name: 'Canada', code: 'CA', dialCode: '+1' },
  { name: 'Australia', code: 'AU', dialCode: '+61' },
  { name: 'Germany', code: 'DE', dialCode: '+49' },
  { name: 'France', code: 'FR', dialCode: '+33' },
  { name: 'China', code: 'CN', dialCode: '+86' },
  { name: 'India', code: 'IN', dialCode: '+91' },
  { name: 'United Arab Emirates', code: 'AE', dialCode: '+971' },
  { name: 'Saudi Arabia', code: 'SA', dialCode: '+966' },
  { name: 'Qatar', code: 'QA', dialCode: '+974' },
];

const getFieldError = (fieldErrors, field) => {
  if (!fieldErrors || !fieldErrors[field]) return null;
  const messages = fieldErrors[field];
  return Array.isArray(messages) ? messages[0] : messages;
};

const clearFieldError = (setFieldErrors, field) => {
  setFieldErrors(prev => ({ ...prev, [field]: null }));
};

export const RegisterPage = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    countryCode: '+251', // Default to Ethiopia
    password: '',
    password_confirmation: '',
  });

  const [showPassword, setShowPassword] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});

  const { register, isLoading } = useAuthStore();
  const navigate = useNavigate();
  const toast = useToast();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setFieldErrors({});

    if (formData.password !== formData.password_confirmation) {
      setFieldErrors(prev => ({ ...prev, password_confirmation: 'Password confirmation does not match.' }));
      return;
    }

    if (formData.password.length < 8) {
      setFieldErrors(prev => ({ ...prev, password: 'Password must be at least 8 characters long.' }));
      return;
    }

    // Combine country code with phone number for storage
    const fullPhone = formData.phone ? `${formData.countryCode}${formData.phone}` : '';

    try {
      await register({ ...formData, phone: fullPhone });
      toast.success('Registration successful!');

      // Redirect to dashboard after registration
      navigate('/dashboard', { replace: true });
    } catch (err) {
      // Handle ApiError with structured errors
      const hasValidationErrors = err.errors && Object.keys(err.errors).length > 0;
      if (hasValidationErrors) {
        const newFieldErrors = {};
        for (const [field, messages] of Object.entries(err.errors)) {
          if (Array.isArray(messages) && messages.length > 0) {
            newFieldErrors[field] = messages[0];
          }
        }
        setFieldErrors(newFieldErrors);
        
        // Show toast for general errors (like duplicate email)
        if (err.friendlyErrors && err.friendlyErrors.length > 0) {
          toast.error(err.friendlyErrors[0]);
        } else if (err.message && !err.message.includes('Validation failed')) {
          toast.error(err.message);
        }
      } else if (err.message) {
        toast.error(err.message);
      }
    }
  };

  const handleChange = (field) => (e) => {
    let value = e.target.value;
    if (field === 'phone') {
      value = value.replace(/\D/g, '');
    }
    setFormData(prev => ({ ...prev, [field]: value }));
    clearFieldError(setFieldErrors, field);
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
            Create Your Account
          </h2>
          <p className="text-xs text-theme-muted">Join Abay Car Rentals for effortless vehicle booking.</p>
        </div>

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
                onChange={handleChange('name')}
                className={`w-full bg-theme-input border rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors ${
                  fieldErrors.name ? 'border-red-500/50' : 'border-theme'
                }`}
              />
            </div>
            {fieldErrors.name && <p className="text-[11px] text-rose-400 mt-1">{fieldErrors.name}</p>}
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
                onChange={handleChange('email')}
                className={`w-full bg-theme-input border rounded-xl pl-10 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors ${
                  fieldErrors.email ? 'border-red-500/50' : 'border-theme'
                }`}
              />
            </div>
            {fieldErrors.email && <p className="text-[11px] text-rose-400 mt-1">{fieldErrors.email}</p>}
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1">Phone Number</label>
            <div className="flex gap-2">
              <div className="relative w-28 shrink-0">
                <Globe className="w-4 h-4 text-theme-muted absolute left-3 top-1/2 -translate-y-1/2" />
                <select
                  value={formData.countryCode}
                  onChange={handleChange('countryCode')}
                  className="w-full bg-theme-input border border-theme rounded-xl pl-8 pr-4 py-2.5 text-sm text-theme-primary focus:outline-none focus:border-blue-500 transition-colors appearance-none bg-no-repeat bg-right-2"
                  style={{ backgroundImage: 'url("data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27%3e%3cpath fill=%27none%27 stroke=%27%23343a40%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%272%27 d=%27M2 5l6 6 6-6%27/%3e%3c/svg%3e")', backgroundSize: '16px 12px' }}
                >
                  {COUNTRY_CODES.map((c) => (
                    <option key={c.dialCode} value={c.dialCode}>
                      {c.name} ({c.dialCode})
                    </option>
                  ))}
                </select>
              </div>
              <div className="relative flex-1">
                <input
                  type="tel"
                  placeholder="Phone number"
                  value={formData.phone}
                  onChange={handleChange('phone')}
                  className="w-full bg-theme-input border border-theme rounded-xl pl-4 pr-4 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
                />
              </div>
            </div>
            {fieldErrors.phone && <p className="text-[11px] text-rose-400 mt-1">{fieldErrors.phone}</p>}
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
                onChange={handleChange('password')}
                className={`w-full bg-theme-input border rounded-xl pl-10 pr-10 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors ${
                  fieldErrors.password ? 'border-red-500/50' : 'border-theme'
                }`}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-primary p-1"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
            {fieldErrors.password && <p className="text-[11px] text-rose-400 mt-1">{fieldErrors.password}</p>}
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
                onChange={handleChange('password_confirmation')}
                className="w-full bg-theme-input border border-theme rounded-xl pl-10 pr-10 py-2.5 text-sm text-theme-primary placeholder-theme-muted focus:outline-none focus:border-blue-500 transition-colors"
              />
            </div>
            {fieldErrors.password_confirmation && <p className="text-[11px] text-rose-400 mt-1">{fieldErrors.password_confirmation}</p>}
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
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
