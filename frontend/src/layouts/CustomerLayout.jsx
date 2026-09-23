import React, { Suspense } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import PageLoader from '../components/common/PageLoader';
import Navbar from '../components/layout/Navbar';
import Footer from '../components/layout/Footer';

export const CustomerLayout = () => {
  const location = useLocation();
  const isHome = location.pathname === '/';

  return (
    <div className="min-h-screen flex flex-col bg-white text-[#0F172A] font-sans">
      <Navbar transparent={isHome} />
      {/* Spacer for fixed navbar — hero handles its own pt-24 */}
      {!isHome && <div className="h-20 shrink-0" />}
      <main className="flex-1">
        <Suspense fallback={<PageLoader />}>
          <Outlet />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
};

export default CustomerLayout;
