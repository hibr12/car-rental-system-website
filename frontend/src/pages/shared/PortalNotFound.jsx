import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { AlertTriangle, Home } from 'lucide-react';

const PortalNotFound = () => {
  const { pathname } = useLocation();

  let portalHome = '/';
  let portalLabel = 'Customer Home';

  if (pathname.startsWith('/admin')) {
    portalHome = '/admin';
    portalLabel = 'Admin Dashboard';
  } else if (pathname.startsWith('/manager') || pathname.startsWith('/branch')) {
    portalHome = '/manager';
    portalLabel = 'Branch Manager Dashboard';
  } else if (pathname.startsWith('/staff')) {
    portalHome = '/staff';
    portalLabel = 'Staff Dashboard';
  } else if (pathname.startsWith('/fleet')) {
    portalHome = '/fleet';
    portalLabel = 'Fleet Dashboard';
  }

  return (
    <div className="min-h-[60vh] flex flex-col items-center justify-center text-center px-4 gap-4">
      <AlertTriangle className="w-12 h-12 text-amber-500" />
      <h1 className="text-2xl font-bold text-theme-primary">Page Not Found</h1>
      <p className="text-sm text-theme-muted max-w-md">
        The page <code className="text-theme-primary">{pathname}</code> does not exist.
      </p>
      <Link
        to={portalHome}
        className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium"
      >
        <Home className="w-4 h-4" />
        Back to {portalLabel}
      </Link>
    </div>
  );
};

export default PortalNotFound;
