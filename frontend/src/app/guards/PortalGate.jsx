import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import useAuthStore from '../../store/authStore';
import AuthLoadingScreen from '../../components/common/AuthLoadingScreen';
import UnauthorizedPage from '../../pages/shared/UnauthorizedPage';
import { getPortalHome, roleMatchesPortal, isCustomerRole } from '../../utils/roles';

/**
 * Guards a management portal (/admin, /manager, /staff, /fleet).
 * Unauthenticated users go to portal sign-in.
 * Wrong-role authenticated users see 403 — never silently redirected to "/".
 */
const PortalGate = ({ portal, layout: Layout }) => {
  const { isInitializing, isAuthenticated, user } = useAuthStore();
  const location = useLocation();
  const loginPath = ['manager', 'branch'].includes(portal)
    ? `/${portal}/login`
    : `/${portal}/login`;

  if (isInitializing) {
    return <AuthLoadingScreen />;
  }

  if (!isAuthenticated || !user) {
    return (
      <Navigate
        to={loginPath}
        replace
        state={{ from: location.pathname }}
      />
    );
  }

  if (!roleMatchesPortal(user.role, portal)) {
    if (isCustomerRole(user.role)) {
      return (
        <Navigate
          to={loginPath}
          replace
          state={{ from: location.pathname, wrongRole: true }}
        />
      );
    }

    return <UnauthorizedPage />;
  }

  return <Layout />;
};

export default PortalGate;
