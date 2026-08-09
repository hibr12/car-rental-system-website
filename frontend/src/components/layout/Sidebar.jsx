import React from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import {
  LayoutDashboard,
  Car,
  CalendarCheck,
  User,
  Users,
  FolderTree,
  CreditCard,
  Star,
  Wrench,
  MessageSquare,
  LogOut,
  ChevronRight,
} from 'lucide-react';
import useAuthStore from '../../store/authStore';

export const Sidebar = ({ isOpen, onClose }) => {
  const { user, logout } = useAuthStore();
  const location = useLocation();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const getMenuGroups = () => {
    switch (user?.role) {
      case 'admin':
        return [
          {
            title: 'Overview',
            items: [{ name: 'Admin Dashboard', path: '/admin', icon: LayoutDashboard }],
          },
          {
            title: 'Management',
            items: [
              { name: 'Vehicles', path: '/admin/vehicles', icon: Car },
              { name: 'Categories', path: '/admin/categories', icon: FolderTree },
              { name: 'User Management', path: '/admin/users', icon: Users },
              { name: 'All Bookings', path: '/admin/bookings', icon: CalendarCheck },
            ],
          },
          {
            title: 'Operations',
            items: [
              { name: 'Payments', path: '/admin/payments', icon: CreditCard },
              { name: 'Maintenance', path: '/admin/maintenance', icon: Wrench },
              { name: 'Contact Messages', path: '/admin/messages', icon: MessageSquare },
            ],
          },
        ];

      case 'fleet_manager':
        return [
          {
            title: 'Fleet Control',
            items: [
              { name: 'Fleet Overview', path: '/fleet', icon: LayoutDashboard },
              { name: 'Vehicle Fleet', path: '/fleet/vehicles', icon: Car },
              { name: 'Maintenance', path: '/fleet/maintenance', icon: Wrench },
            ],
          },
        ];

      case 'staff':
        return [
          {
            title: 'Staff Workstation',
            items: [
              { name: 'Workstation Overview', path: '/staff', icon: LayoutDashboard },
              { name: 'Bookings & Returns', path: '/staff/bookings', icon: CalendarCheck },
            ],
          },
        ];

      default: // customer
        return [
          {
            title: 'Customer Menu',
            items: [
              { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
              { name: 'My Bookings', path: '/dashboard/bookings', icon: CalendarCheck },
              { name: 'My Reviews', path: '/dashboard/reviews', icon: Star },
              { name: 'Profile Settings', path: '/dashboard/profile', icon: User },
            ],
          },
        ];
    }
  };

  const menuGroups = getMenuGroups();

  return (
    <>
      {/* Mobile Backdrop */}
      {isOpen && (
        <div
          onClick={onClose}
          className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm md:hidden"
        />
      )}

      <aside
        className={`fixed top-0 left-0 bottom-0 z-50 w-64 bg-theme-card border-r border-theme flex flex-col justify-between transition-transform duration-300 md:translate-x-0 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div>
          {/* Logo Header */}
          <div className="h-20 flex items-center px-6 border-b border-theme justify-between">
            <Link to="/" className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-md">
                <Car className="w-5 h-5 text-white" />
              </div>
              <span className="text-lg font-bold tracking-tight text-theme-primary">
                Apex<span className="text-blue-400">Rentals</span>
              </span>
            </Link>
          </div>

          {/* Navigation Links */}
          <div className="py-6 px-4 space-y-6 overflow-y-auto max-h-[calc(100vh-160px)]">
            {menuGroups.map((group, idx) => (
              <div key={idx} className="space-y-2">
                <p className="px-3 text-[11px] font-semibold uppercase tracking-wider text-theme-muted">
                  {group.title}
                </p>
                <div className="space-y-1">
                  {group.items.map((item) => {
                    const Icon = item.icon;
                    const isActive = location.pathname === item.path;
                    return (
                      <Link
                        key={item.path}
                        to={item.path}
                        onClick={onClose}
                        className={`flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
                          isActive
                            ? 'bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/20'
                            : 'text-theme-muted hover:text-theme-primary hover:bg-theme-hover'
                        }`}
                      >
                        <div className="flex items-center gap-3">
                          <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-theme-muted'}`} />
                          <span>{item.name}</span>
                        </div>
                        {isActive && <ChevronRight className="w-4 h-4 text-white/70" />}
                      </Link>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* User Info & Logout Footer */}
        <div className="p-4 border-t border-theme bg-theme-card/50">
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors"
          >
            <LogOut className="w-4 h-4" />
            <span>Sign Out</span>
          </button>
        </div>
      </aside>
    </>
  );
};

export default Sidebar;
