import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';
import ManagementSidebar from '../components/shared/ManagementSidebar';
import ManagementTopbar from '../components/shared/ManagementTopbar';

export const StaffLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className="min-h-screen bg-theme-primary text-theme-primary flex font-sans transition-colors duration-200">
      <ManagementSidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
      <div className="flex-1 flex flex-col min-w-0 md:pl-64">
        <ManagementTopbar onToggleSidebar={() => setSidebarOpen(!sidebarOpen)} />
        <main className="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default StaffLayout;
