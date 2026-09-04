import React, { useState, useEffect } from 'react';
import { Outlet } from 'react-router-dom';
import ManagementSidebar from '../components/shared/ManagementSidebar';
import ManagementTopbar from '../components/shared/ManagementTopbar';
import useThemeStore from '../store/themeStore';

/**
 * Independent scroll: sidebar + main content each overflow-y-auto; shell is fixed h-screen.
 * Management UI is always light/white — dark mode does not apply to this shell.
 */
const ManagementDashboardLayout = ({ portal = 'admin' }) => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const setTheme = useThemeStore((s) => s.setTheme);

  useEffect(() => {
    // Force light theme for all management portals
    setTheme('light');
    document.documentElement.classList.remove('dark');
    document.documentElement.classList.add('light');
  }, [setTheme]);

  return (
    <div className="management-shell h-screen overflow-hidden flex bg-white text-[#0F172A] font-sans">
      <ManagementSidebar
        portal={portal}
        isOpen={sidebarOpen}
        onClose={() => setSidebarOpen(false)}
      />

      <div className="flex-1 flex flex-col min-w-0 h-screen overflow-hidden md:ml-64 bg-white">
        <ManagementTopbar
          portal={portal}
          onToggleSidebar={() => setSidebarOpen(!sidebarOpen)}
        />
        <main className="flex-1 overflow-y-auto overflow-x-hidden bg-white">
          <div className="p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto bg-white text-[#0F172A]">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
};

export default ManagementDashboardLayout;
