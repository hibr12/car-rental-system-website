import React from 'react';
import { NavLink, useLocation, useNavigate } from 'react-router-dom';
import {
  Car,
  LayoutDashboard,
  Users,
  CarFront,
  FolderTree,
  CalendarCheck,
  CreditCard,
  Star,
  Wrench,
  MessageSquare,
  BarChart3,
  LogOut,
  X,
  Ship,
  ClipboardList,
} from 'lucide-react';
import useAuthStore from '../../store/authStore';

const adminNavItems = [
  { label: 'Dashboard', path: '/admin', icon: LayoutDashboard },
  { label: 'Users', path: '/admin/users', icon: Users },
  { label: 'Vehicles', path: '/admin/vehicles', icon: CarFront },
  { label: 'Categories', path: '/admin/categories', icon: FolderTree },
  { label: 'Bookings', path: '/admin/bookings', icon: CalendarCheck },
  { label: 'Payments', path: '/admin/payments', icon: CreditCard },
  { label: 'Reviews', path: '/admin/reviews', icon: Star },
  { label: 'Maintenance', path: '/admin/maintenance', icon: Wrench },
  { label: 'Messages', path: '/admin/messages', icon: MessageSquare },
  { label: 'Analytics', path: '/admin/analytics', icon: BarChart3 },
];

const fleetNavItems = [
  { label: 'Fleet Overview', path: '/fleet', icon: Ship },
  { label: 'Vehicles', path: '/fleet/vehicles', icon: CarFront },
  { label: 'Maintenance', path: '/fleet/maintenance', icon: Wrench },
];

const staffNavItems = [
  { label: 'Dashboard', path: '/staff', icon: LayoutDashboard },
  { label: 'Bookings', path: '/staff/bookings', icon: ClipboardList },
];

const getNavItems = (role) => {
  switch (role) {
    case 'admin':
      return adminNavItems;
    case 'fleet_manager':
      return fleetNavItems;
    case 'staff':
      return staffNavItems;
    default:
      return [];
  }
};

const ManagementSidebar = ({ isOpen, onClose }) => {
  const location = useLocation();
  const navigate = useNavigate();
  const { user, logout } = useAuthStore();
  const navItems = getNavItems(user?.role);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={onClose}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`
          fixed top-0 left-0 z-50 h-full w-64
          bg-[#0f172a] border-r border-[#1e293b]
          transform transition-transform duration-200 ease-in-out
          lg:translate-x-0
          ${isOpen ? 'translate-x-0' : '-translate-x-full'}
        `}
      >
        <div className="flex flex-col h-full">
          {/* Branding */}
          <div className="p-5 border-b border-[#1e293b]">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center">
                <Car className="w-5 h-5 text-white" />
              </div>
              <div>
                <h1 className="text-white font-bold text-base leading-tight">
                  Apex Rentals
                </h1>
                <p className="text-[#94a3b8] text-[11px] font-medium">
                  Administration
                </p>
              </div>
            </div>

            {/* Mobile close button */}
            <button
              onClick={onClose}
              className="absolute top-4 right-3 p-1 rounded-lg text-[#94a3b8] hover:text-white hover:bg-[#1e293b] transition-colors lg:hidden"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Navigation */}
          <nav className="flex-1 overflow-y-auto py-4 px-3">
            <div className="space-y-1">
              {navItems.map((item) => {
                const Icon = item.icon;
                const isActive =
                  item.path === '/admin' || item.path === '/fleet' || item.path === '/staff'
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
                      ${
                        isActive
                          ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/25'
                          : 'text-[#94a3b8] hover:text-white hover:bg-[#1e293b]'
                      }
                    `}
                  >
                    <Icon className="w-[18px] h-[18px] shrink-0" />
                    <span>{item.label}</span>
                  </NavLink>
                );
              })}
            </div>
          </nav>

          {/* Logout */}
          <div className="p-3 border-t border-[#1e293b]">
            <button
              onClick={handleLogout}
              className="
                flex items-center gap-3 w-full px-3 py-2.5 rounded-xl
                text-sm font-medium text-[#94a3b8]
                hover:text-white hover:bg-[#1e293b]
                transition-all duration-150
              "
            >
              <LogOut className="w-[18px] h-[18px] shrink-0" />
              <span>Logout</span>
            </button>
          </div>
        </div>
      </aside>
    </>
  );
};

export default ManagementSidebar;
