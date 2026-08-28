export const ROLES = {
  SUPER_ADMIN: 'super_admin',
  ADMIN: 'admin',
  BRANCH_MANAGER: 'branch_manager',
  FLEET_MANAGER: 'fleet_manager',
  STAFF: 'staff',
  RENTAL_AGENT: 'rental_agent',
  INSPECTION_STAFF: 'inspection_staff',
  MAINTENANCE_STAFF: 'maintenance_staff',
  FINANCE_STAFF: 'finance_staff',
  CUSTOMER: 'customer',
};

export const ADMIN_ROLES = [ROLES.SUPER_ADMIN, ROLES.ADMIN];
export const BRANCH_ROLES = [ROLES.BRANCH_MANAGER];
export const FLEET_ROLES = [ROLES.FLEET_MANAGER];
export const STAFF_ROLES = [
  ROLES.STAFF,
  ROLES.RENTAL_AGENT,
  ROLES.INSPECTION_STAFF,
  ROLES.MAINTENANCE_STAFF,
  ROLES.FINANCE_STAFF,
];

export const MANAGEMENT_ROLES = [
  ...ADMIN_ROLES,
  ...BRANCH_ROLES,
  ...FLEET_ROLES,
  ...STAFF_ROLES,
];

export function isAdminRole(role) {
  return ADMIN_ROLES.includes(role);
}

export function isBranchManagerRole(role) {
  return BRANCH_ROLES.includes(role);
}

export function isFleetManagerRole(role) {
  return FLEET_ROLES.includes(role);
}

export function isStaffRole(role) {
  return STAFF_ROLES.includes(role);
}

export function isCustomerRole(role) {
  return role === ROLES.CUSTOMER || !role;
}

/** Default portal home path for a role */
export function getPortalHome(role) {
  if (isAdminRole(role)) return '/admin';
  if (isBranchManagerRole(role)) return '/branch';
  if (isFleetManagerRole(role)) return '/fleet';
  if (isStaffRole(role)) return '/staff';
  return '/';
}

/** Which portal prefix a role belongs to */
export function getPortalPrefix(role) {
  if (isAdminRole(role)) return '/admin';
  if (isBranchManagerRole(role)) return '/branch';
  if (isFleetManagerRole(role)) return '/fleet';
  if (isStaffRole(role)) return '/staff';
  return '/';
}

export function roleMatchesPortal(role, portal) {
  switch (portal) {
    case 'admin':   return isAdminRole(role);
    case 'manager':
    case 'branch':  return isBranchManagerRole(role);
    case 'fleet':   return isFleetManagerRole(role);
    case 'staff':   return isStaffRole(role);
    default:        return false;
  }
}
