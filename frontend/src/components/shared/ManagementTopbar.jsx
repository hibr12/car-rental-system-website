import React from 'react';
import { Menu } from 'lucide-react';
import useAuthStore from '../../store/authStore';
import NotificationBell from '../common/NotificationBell';

const ManagementTopbar = ({ portal = 'admin', onToggleSidebar, pageTitle }) => {
  const { user } = useAuthStore();

  const getRoleBadge = (role) => {
    const badges = {
      super_admin:    { label: 'Super Admin',    color: 'bg-blue-50 text-[#2563EB]' },
      admin:          { label: 'Admin',          color: 'bg-blue-50 text-[#2563EB]' },
      branch_manager: { label: 'Branch Manager', color: 'bg-blue-50 text-[#2563EB]' },
      fleet_manager:  { label: 'Fleet Manager',  color: 'bg-amber-50 text-[#F59E0B]' },
      staff:          { label: 'Staff',          color: 'bg-slate-100 text-[#64748B]' },
      rental_agent:   { label: 'Rental Agent',   color: 'bg-slate-100 text-[#64748B]' },
    };
    return badges[role] || { label: role?.replace('_', ' '), color: 'bg-slate-100 text-[#64748B]' };
  };

  const roleBadge = getRoleBadge(user?.role);
  const branchName = user?.branch?.name;

  return (
    <header className="shrink-0 z-30 bg-white border-b border-[#E2E8F0]">
      <div className="flex items-center justify-between h-16 px-4 sm:px-6">
        <div className="flex items-center gap-4">
          <button
            onClick={onToggleSidebar}
            className="p-2 rounded-xl bg-white border border-[#E2E8F0] text-[#334155] hover:bg-[#F8FAFC] transition-all lg:hidden"
            aria-label="Toggle sidebar"
          >
            <Menu className="w-5 h-5" />
          </button>

          <div className="flex flex-col">
            {pageTitle ? (
              <h1 className="text-lg font-bold text-[#0F172A]">{pageTitle}</h1>
            ) : (
              <h1 className="text-lg font-bold text-[#0F172A] capitalize">{portal} Portal</h1>
            )}
            {branchName && (
              <span className="text-[11px] text-[#64748B]">{branchName}</span>
            )}
          </div>
        </div>

        <div className="flex items-center gap-3">
          <NotificationBell />

          <div className="hidden sm:flex items-center gap-3 pl-3 border-l border-[#E2E8F0]">
            <div className="w-9 h-9 rounded-xl bg-[#2563EB]/10 flex items-center justify-center text-[#2563EB] font-bold text-sm">
              {user?.name?.charAt(0)?.toUpperCase() || 'U'}
            </div>
            <div className="flex flex-col">
              <span className="text-sm font-semibold text-[#0F172A] leading-tight">
                {user?.name || 'User'}
              </span>
              <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full w-fit ${roleBadge.color}`}>
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
