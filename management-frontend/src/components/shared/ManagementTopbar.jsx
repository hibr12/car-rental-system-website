import React from 'react';
import { Menu, Sun, Moon, ChevronRight } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import useThemeStore from '../../store/themeStore';
import NotificationBell from '../common/NotificationBell';

const ManagementTopbar = ({ onToggleSidebar, pageTitle }) => {
  const { user } = useAuthStore();
  const { theme, toggleTheme } = useThemeStore();

  const getRoleBadge = (role) => {
    const badges = {
      admin: { label: 'Admin', color: 'bg-blue-500/15 text-blue-400' },
      fleet_manager: { label: 'Fleet Manager', color: 'bg-emerald-500/15 text-emerald-400' },
      staff: { label: 'Staff', color: 'bg-amber-500/15 text-amber-400' },
    };
    return badges[role] || { label: role, color: 'bg-gray-500/15 text-gray-400' };
  };

  const roleBadge = getRoleBadge(user?.role);

  return (
    <header className="sticky top-0 z-30 bg-theme-primary/80 backdrop-blur-xl border-b border-theme">
      <div className="flex items-center justify-between h-16 px-4 sm:px-6">
        {/* Left */}
        <div className="flex items-center gap-4">
          <button
            onClick={onToggleSidebar}
            className="p-2 rounded-xl bg-theme-secondary border border-theme hover:border-theme-hover text-theme-secondary hover:text-theme-primary transition-all lg:hidden"
            aria-label="Toggle sidebar"
          >
            <Menu className="w-5 h-5" />
          </button>

          <div className="flex items-center gap-2">
            <h1 className="text-lg font-bold text-theme-primary">{pageTitle}</h1>
          </div>
        </div>

        {/* Right */}
        <div className="flex items-center gap-3">
          <NotificationBell />

          <button
            onClick={toggleTheme}
            className="p-2 rounded-xl bg-theme-secondary border border-theme hover:border-theme-hover text-theme-secondary hover:text-theme-primary transition-all"
            aria-label="Toggle theme"
          >
            {theme === 'dark' ? (
              <Sun className="w-4 h-4" />
            ) : (
              <Moon className="w-4 h-4" />
            )}
          </button>

          {/* User info */}
          <div className="hidden sm:flex items-center gap-3 pl-3 border-l border-theme">
            <div className="w-9 h-9 rounded-xl bg-blue-600/15 flex items-center justify-center text-blue-400 font-bold text-sm">
              {user?.name?.charAt(0)?.toUpperCase() || 'U'}
            </div>
            <div className="flex flex-col">
              <span className="text-sm font-semibold text-theme-primary leading-tight">
                {user?.name || 'User'}
              </span>
              <span
                className={`text-[10px] font-semibold px-2 py-0.5 rounded-full w-fit ${roleBadge.color}`}
              >
                {roleBadge.label}
              </span>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
};

export default ManagementTopbar;
