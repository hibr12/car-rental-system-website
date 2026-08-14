import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';

/** Redirect legacy /branch/* URLs to /manager/* */
const LegacyBranchRedirect = () => {
  const { pathname } = useLocation();
  const target = pathname.replace(/^\/branch/, '/manager') || '/manager';
  return <Navigate to={target} replace />;
};

export default LegacyBranchRedirect;
