import React from 'react';
import { Link } from 'react-router-dom';
import { Menu, ExternalLink } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import useThemeStore from '../../store/themeStore';
import { getRoleBadgeStyle, formatStatus } from '../../utils/formatters';

export const Topbar = ({ onToggleSidebar }) => {
  const { user } = useAuthStore();
  const { theme, toggleTheme } = useThemeStore();

  return (
    <header className="h-20 bg-theme-card/80 border-b border-theme backdrop-blur-md sticky top-0 z-30 px-4 sm:px-8 flex items-center justify-between transition-colors duration-200">
      <div className="flex items-center gap-4">
        <button
          onClick={onToggleSidebar}
          className="md:hidden p-2 rounded-xl bg-theme-secondary text-theme-secondary hover:text-theme-primary"
          aria-label="Toggle Sidebar Menu"
        >
          <Menu className="w-5 h-5" />
        </button>

        <div className="hidden sm:flex items-center gap-2">
          <span className="text-xs text-theme-muted font-medium">Welcome back,</span>
          <span className="text-sm font-semibold text-theme-primary">{user?.name}</span>
        </div>
      </div>

      <div className="flex items-center gap-4">
        {/* Theme Toggle */}
        <button
          onClick={toggleTheme}
          className="p-2 rounded-xl bg-theme-secondary border border-theme hover:border-theme-hover text-theme-secondary hover:text-theme-primary transition-all"
          aria-label="Toggle theme"
        >
          {theme === 'dark' ? (
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          ) : (
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          )}
        </button>

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
          className="hidden sm:flex items-center gap-2 text-xs font-medium text-theme-muted hover:text-blue-400 transition-colors px-3 py-1.5 rounded-lg border border-theme hover:border-theme-hover bg-theme-secondary"
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
