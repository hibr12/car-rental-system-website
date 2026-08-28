import React from 'react';
import { NavLink, useLocation, useNavigate } from 'react-router-dom';
import {
  Car, LayoutDashboard, Users, CarFront, FolderTree, CalendarCheck,
  CreditCard, Star, Wrench, MessageSquare, BarChart3, LogOut, X, Ship,
  ClipboardList, Building2, ArrowRightLeft, UserCog, TrendingUp, Truck,
  UserCircle, LogIn, LogOutIcon, Eye, Archive, ShieldCheck, AlertTriangle, Bell,
} from 'lucide-react';
import useAuthStore from '../../store/authStore';

const adminNavItems = [
  { label: 'Dashboard',        path: '/admin',                  icon: LayoutDashboard, exact: true },
  { label: 'Branches',         path: '/admin/branches',         icon: Building2 },
  { label: 'Users',            path: '/admin/users',            icon: Users },
  { label: 'Staff',            path: '/admin/staff',            icon: UserCog },
  { label: 'Vehicles',         path: '/admin/vehicles',         icon: CarFront },
  { label: 'Transfers',        path: '/admin/transfers',        icon: ArrowRightLeft },
  { label: 'Categories',       path: '/admin/categories',       icon: FolderTree },
  { label: 'Bookings',         path: '/admin/bookings',         icon: CalendarCheck },
  { label: 'Payments',         path: '/admin/payments',         icon: CreditCard },
  { label: 'Payment History',  path: '/admin/payment-history',  icon: CreditCard },
  { label: 'Reconciliation',   path: '/admin/payments/reconciliation', icon: BarChart3 },
  { label: 'Reviews',          path: '/admin/reviews',          icon: Star },
  { label: 'Licenses',         path: '/admin/licenses',         icon: ShieldCheck },
  { label: 'Maintenance',      path: '/admin/maintenance',      icon: Wrench },
  { label: 'Messages',         path: '/admin/messages',         icon: MessageSquare },
  { label: 'Reports',          path: '/admin/reports',          icon: TrendingUp },
  { label: 'Analytics',        path: '/admin/analytics',        icon: BarChart3 },
  { label: 'Archive',          path: '/admin/archive',          icon: Archive },
];

const getBranchNavItems = (base) => [
  { label: 'Dashboard',        path: base,                      icon: LayoutDashboard, exact: true },
  { label: 'Bookings',         path: `${base}/bookings`,        icon: CalendarCheck },
  { label: 'Pickup & Return',  path: `${base}/rentals`,         icon: LogIn },
  { label: 'Vehicles',         path: `${base}/vehicles`,        icon: CarFront },
  { label: 'Customers',        path: `${base}/customers`,       icon: Users },
  { label: 'Payments',         path: `${base}/payments`,        icon: CreditCard },
  { label: 'Maintenance Req.', path: `${base}/maintenance-requests`, icon: Wrench },
  { label: 'Transfers',        path: `${base}/transfers`,       icon: ArrowRightLeft },
  { label: 'Reviews',          path: `${base}/reviews`,         icon: Star },
  { label: 'Staff',            path: `${base}/staff`,           icon: UserCog },
  { label: 'Licenses',         path: `${base}/licenses`,        icon: ShieldCheck },
  { label: 'Reports',          path: `${base}/reports`,         icon: TrendingUp },
  { label: 'Notifications',  path: `${base}/notifications`,   icon: Bell },
];

const managerNavItems = getBranchNavItems('/manager');

const fleetNavItems = [
  { label: 'Fleet Dashboard', path: '/fleet',             icon: LayoutDashboard, exact: true },
  { label: 'Vehicles',        path: '/fleet/vehicles',    icon: CarFront },
  { label: 'Inspections',     path: '/fleet/inspections', icon: ClipboardList },
  { label: 'Maintenance',     path: '/fleet/maintenance', icon: Wrench },
  { label: 'Transfers',       path: '/fleet/transfers',   icon: ArrowRightLeft },
  { label: 'Documents',       path: '/fleet/documents',   icon: ShieldCheck },
  { label: 'Damage',          path: '/fleet/damage',      icon: AlertTriangle },
  { label: 'Fleet Reports',   path: '/fleet/reports',     icon: TrendingUp },
];

const staffNavItems = [
  { label: 'Dashboard',       path: '/staff',                 icon: LayoutDashboard, exact: true },
  { label: 'Bookings',        path: '/staff/bookings',        icon: ClipboardList },
  { label: 'Payments',        path: '/staff/payments',        icon: CreditCard },
  { label: 'Payment History', path: '/staff/payment-history', icon: CreditCard },
  { label: 'Vehicles',        path: '/staff/vehicles',        icon: CarFront },
  { label: 'Maintenance',     path: '/staff/maintenance',     icon: Wrench },
];

const PORTAL_META = {
  admin:   { label: 'Administration', accent: 'bg-[#2563EB]' },
  manager: { label: 'Branch Manager', accent: 'bg-[#2563EB]' },
  branch:  { label: 'Branch Manager', accent: 'bg-[#2563EB]' },
  fleet:   { label: 'Fleet Manager',  accent: 'bg-[#2563EB]' },
  staff:   { label: 'Staff Portal',   accent: 'bg-[#2563EB]' },
};

const getNavItemsForPortal = (portal) => {
  switch (portal) {
    case 'admin':   return adminNavItems;
    case 'manager': return managerNavItems;
    case 'branch':  return getBranchNavItems('/branch');
    case 'fleet':   return fleetNavItems;
    case 'staff':   return staffNavItems;
    default:        return [];
  }
};

const ManagementSidebar = ({ portal = 'admin', isOpen, onClose }) => {
  const location = useLocation();
  const navigate = useNavigate();
  const { user, logout } = useAuthStore();
  const navItems = getNavItemsForPortal(portal);
  const meta = PORTAL_META[portal] || PORTAL_META.admin;
  const loginPortal = portal === 'branch' ? 'branch' : portal === 'manager' ? 'manager' : portal;

  const handleLogout = async () => {
    await logout();
    navigate(`/${loginPortal}/login`);
  };

  const branchName = user?.branch?.name;

  return (
    <>
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={onClose}
        />
      )}

      <aside
        className={`
          fixed top-0 left-0 z-50 w-64 h-screen
          bg-[#0F172A] border-r border-[#1e293b]
          flex flex-col overflow-hidden
          transform transition-transform duration-200 ease-in-out
          lg:translate-x-0
          ${isOpen ? 'translate-x-0' : '-translate-x-full'}
        `}
      >
        <div className="shrink-0 p-5 border-b border-[#1e293b] relative">
          <div className="flex items-center gap-3">
            <div className={`w-9 h-9 rounded-xl ${meta.accent} flex items-center justify-center`}>
              <Car className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="text-white font-bold text-base leading-tight">Apex Rentals</h1>
              <p className="text-[#CBD5E1] text-[11px] font-medium">{meta.label}</p>
              {branchName && (
                <p className="text-[#0EA5E9] text-[10px] font-semibold mt-0.5">{branchName}</p>
              )}
            </div>
          </div>
          <button
            onClick={onClose}
            className="absolute top-4 right-3 p-1 rounded-lg text-[#CBD5E1] hover:text-white hover:bg-[#1e293b] transition-colors lg:hidden"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <nav className="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3">
          <div className="space-y-1">
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = item.exact
                ? location.pathname === item.path
                : location.pathname.startsWith(item.path);

              return (
                <NavLink
                  key={item.path}
                  to={item.path}
                  onClick={onClose}
                  className={`
                    flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                    transition-all duration-150
                    ${isActive
                      ? 'bg-[#2563EB] text-white'
                      : 'text-[#CBD5E1] hover:text-white hover:bg-[#1e293b]'}
                  `}
                >
                  <Icon className="w-[18px] h-[18px] shrink-0" />
                  <span>{item.label}</span>
                </NavLink>
              );
            })}
          </div>
        </nav>

        <div className="shrink-0 p-3 border-t border-[#1e293b]">
          <button
            onClick={handleLogout}
            className="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium text-[#CBD5E1] hover:text-white hover:bg-[#1e293b] transition-all duration-150"
          >
            <LogOut className="w-[18px] h-[18px] shrink-0" />
            <span>Logout</span>
          </button>
        </div>
      </aside>
    </>
  );
};

export default ManagementSidebar;
