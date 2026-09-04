import React, { useState } from 'react';
import { User, Mail, Phone, Save, ShieldCheck } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import { useToast } from '../../components/common/Toast';

export const CustomerProfile = () => {
  const { user, updateProfile, isLoading } = useAuthStore();
  const toast = useToast();

  const [name, setName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [phone, setPhone] = useState(user?.phone || '');

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await updateProfile({ name, email, phone });
      toast.success('Profile details updated successfully!');
    } catch (err) {
      toast.error(err.message || 'Failed to update profile.');
    }
  };

  return (
    <div className="max-w-3xl mx-auto space-y-8">
      <div className="border-b border-theme pb-6">
        <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Account Profile</h1>
        <p className="text-sm text-theme-muted">Manage your personal details and contact preferences.</p>
      </div>

      <div className="bg-theme-card border border-theme p-8 rounded-3xl space-y-6 shadow-2xl">
        <div className="flex items-center gap-4 pb-6 border-b border-theme">
          <div className="w-16 h-16 rounded-full bg-blue-600 text-white font-extrabold text-2xl flex items-center justify-center shadow-lg">
            {name?.[0]?.toUpperCase() || 'U'}
          </div>
          <div>
            <h3 className="text-xl font-bold text-theme-primary">{user?.name}</h3>
            <p className="text-xs text-theme-muted">{user?.email}</p>
            <span className="inline-block mt-1 px-2.5 py-0.5 text-[10px] uppercase font-bold rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">
              Role: {user?.role}
            </span>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Full Name</label>
            <div className="relative">
              <User className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                required
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full bg-theme-secondary border border-theme rounded-xl pl-10 pr-4 py-3 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Email Address</label>
            <div className="relative">
              <Mail className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full bg-theme-secondary border border-theme rounded-xl pl-10 pr-4 py-3 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-theme-secondary mb-1.5">Phone Number</label>
            <div className="relative">
              <Phone className="w-4 h-4 text-theme-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="+1 (555) 000-0000"
                className="w-full bg-theme-secondary border border-theme rounded-xl pl-10 pr-4 py-3 text-sm text-theme-primary focus:outline-none focus:border-blue-500"
              />
            </div>
          </div>

          <div className="pt-4">
            <button
              type="submit"
              disabled={isLoading}
              className="px-6 py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-lg disabled:opacity-50 flex items-center gap-2"
            >
              <Save className="w-4 h-4" />
              <span>{isLoading ? 'Saving Changes...' : 'Save Profile Changes'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CustomerProfile;
