import React from 'react';
import { Navigate } from 'react-router-dom';
import useAuthStore from '../store/authStore';

export const RoleRoute = ({ children, allowedRoles = [] }) => {
  const { user } = useAuthStore();

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
    switch (user.role) {
      case 'admin':
        return <Navigate to="/admin" replace />;
      case 'fleet_manager':
        return <Navigate to="/fleet" replace />;
      case 'branch_manager':
        return <Navigate to="/branch-manager" replace />;
      case 'staff':
        return <Navigate to="/staff" replace />;
      default:
        return <Navigate to="/dashboard" replace />;
    }
  }

  return children;
};

export default RoleRoute;
