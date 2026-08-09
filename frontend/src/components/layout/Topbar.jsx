import React from 'react';
import { Link } from 'react-router-dom';
import { Menu, Bell, User, ExternalLink } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import { getRoleBadgeStyle, formatStatus } from '../../utils/formatters';

export const Topbar = ({ onToggleSidebar }) => {
  const { user } = useAuthStore();

  return (
    <header className="h-20 bg-slate-900/80 border-b border-slate-800 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-8 flex items-center justify-between">
      <div className="flex items-center gap-4">
        <button
          onClick={onToggleSidebar}
          className="md:hidden p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
          aria-label="Toggle Sidebar Menu"
        >
          <Menu className="w-5 h-5" />
        </button>

        <div className="hidden sm:flex items-center gap-2">
          <span className="text-xs text-slate-400 font-medium">Welcome back,</span>
          <span className="text-sm font-semibold text-slate-100">{user?.name}</span>
        </div>
      </div>

      <div className="flex items-center gap-4">
        {/* Role Badge */}
        <span
          className={`px-3 py-1 text-xs font-bold uppercase rounded-full border ${getRoleBadgeStyle(
            user?.role
          )}`}
        >
          {formatStatus(user?.role)}
        </span>

        {/* Link back to public site */}
        <Link
          to="/"
          className="hidden sm:flex items-center gap-2 text-xs font-medium text-slate-400 hover:text-blue-400 transition-colors px-3 py-1.5 rounded-lg border border-slate-800 hover:border-slate-700 bg-slate-900"
        >
          <span>Main Website</span>
          <ExternalLink className="w-3.5 h-3.5" />
        </Link>

        {/* User Avatar */}
        <div className="w-9 h-9 rounded-full bg-blue-600/30 text-blue-400 font-bold text-sm flex items-center justify-center border border-blue-500/30 shadow-inner">
          {user?.name?.[0]?.toUpperCase() || 'U'}
        </div>
      </div>
    </header>
  );
};

export default Topbar;
