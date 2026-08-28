import React from 'react';
import { Navigate } from 'react-router-dom';
import useAuthStore from '../../store/authStore';

/**
 * Restricts access to users with one of the allowed roles.
 * Redirects authenticated users to their own portal if they try
 * to access an unauthorized one.
 */
const RoleRoute = ({ children, allowedRoles = [] }) => {
  const { user } = useAuthStore();

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
    switch (user.role) {
      case 'admin':
      case 'super_admin':     return <Navigate to="/admin"  replace />;
      case 'branch_manager':  return <Navigate to="/manager" replace />;
      case 'fleet_manager':   return <Navigate to="/fleet"  replace />;
      case 'staff':
      case 'rental_agent':
      case 'inspection_staff':
      case 'maintenance_staff':
      case 'finance_staff':   return <Navigate to="/staff"  replace />;
      case 'customer':        return <Navigate to="/"       replace />;
      default:                return <Navigate to="/login"  replace />;
    }
  }

  return children;
};

export default RoleRoute;
