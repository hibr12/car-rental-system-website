import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { ShieldX, Home } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import { getPortalHome } from '../../utils/roles';

const UnauthorizedPage = () => {
  const { pathname } = useLocation();
  const { user } = useAuthStore();
  const home = user ? getPortalHome(user.role) : '/';

  return (
    <div className="min-h-[60vh] flex flex-col items-center justify-center text-center px-4 gap-4 bg-white">
      <ShieldX className="w-12 h-12 text-[#DC2626]" />
      <h1 className="text-2xl font-bold text-[#0F172A]">403 — Unauthorized</h1>
      <p className="text-sm text-[#64748B] max-w-md">
        You do not have permission to access{' '}
        <code className="text-[#0F172A]">{pathname}</code>.
      </p>
      <Link
        to={home}
        className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-medium"
      >
        <Home className="w-4 h-4" />
        Go to your dashboard
      </Link>
    </div>
  );
};

export default UnauthorizedPage;
