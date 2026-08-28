# Car Rental System - Role Functions & Permissions

## Overview
This document describes the complete role-based access control (RBAC) system for the Apex Rentals car rental management platform. Each role has specific permissions, portal access, and capabilities.

---

## Role Hierarchy

```
super_admin (inherits admin)
    ↓
admin
    ↓
branch_manager ←→ fleet_manager (parallel, different scopes)
    ↓
staff
    ↓
customer
```

---

## 1. SUPER_ADMIN
**Portal:** `/admin/login`
**Inherits:** All admin permissions + system-level access

### Capabilities
- Full system administration
- Can impersonate any user
- Access to all companies, branches, and data
- System configuration and maintenance
- Database migration management
- Audit log access

---

## 2. ADMIN (Company Admin)
**Portal:** `/admin/login`
**Scope:** Company-wide (all branches)

### Dashboard & Analytics
- Company dashboard with fleet utilization, revenue reports
- Cross-branch analytics and reporting
- Fleet management across all branches

### Branch Management
- Create, read, update, delete branches
- Activate/deactivate branches
- Assign/reassign branch managers
- View branch staff, vehicles, bookings, payments

### Vehicle Management
- Full CRUD on all vehicles across all branches
- Vehicle transfers between branches (initiate, approve, execute)
- Vehicle status management
- Vehicle images, documents, inspections, damages

### Booking Management
- View all bookings across company
- Confirm, reject, prepare pickup, pickup, return vehicles
- Override booking workflows
- Archive bookings

### Payment Management
- View all payments across company
- Payment reconciliation
- Confirm cash payments
- Mark payments as failed
- Process refunds
- Payment history

### User Management
- Create, read, update, delete staff/users
- Assign roles and branches
- Manage branch managers

### Fleet & Maintenance
- Full maintenance management across all branches
- Vehicle inspections, documents, damages
- Fleet reports and analytics

### Reviews & Licenses
- Review management (approve, reject, respond)
- Driver license verification (approve/reject)

### Reports
- Company revenue reports
- Fleet utilization reports
- Branch performance reports

---

## 3. BRANCH MANAGER
**Portal:** `/branch/login` (also accessible via `/manager/login`)
**Scope:** Single assigned branch only

### Dashboard
- Branch-specific dashboard with KPIs
- Available/rented/maintenance vehicle counts
- Active/pending bookings
- Today's revenue and monthly revenue
- Maintenance alerts
- Recent bookings

### Customer Management
- View branch customers
- Customer booking history

### Vehicle Management
- View vehicles assigned to their branch
- Create, update vehicles (branch_id auto-assigned)
- Vehicle status updates
- Vehicle images

### Booking Management
- View branch bookings
- Confirm, reject, prepare pickup, pickup, return
- Check-in/check-out (rentals)

### Payment Management
- View branch payments
- Confirm cash payments
- View payment history

### Staff Management
- View branch staff
- Create, update staff (auto-assigned to branch)

### Transfers
- Initiate vehicle transfers (as source branch)
- Prepare release, mark in-transit, receive vehicles
- View transfer history

### Maintenance Requests
- Create maintenance requests for branch vehicles
- View maintenance status

### Reports
- Branch revenue reports
- Fleet utilization for branch

### Reviews
- View reviews for branch vehicles

### Licenses
- View driver licenses for branch customers

### Notifications
- Branch-specific notifications

---

## 4. FLEET MANAGER
**Portal:** `/fleet/login`
**Scope:** Company-wide (fleet operations only)

### Dashboard
- Fleet overview dashboard
- Vehicle status across all branches
- Maintenance alerts

### Vehicle Management
- Full CRUD on all vehicles across all branches
- Vehicle status management
- Vehicle images, documents, inspections, damages

### Maintenance Management
- Full maintenance CRUD across all branches
- Schedule, track, complete maintenance
- Maintenance history

### Vehicle Transfers
- View all transfers
- Approve, reject, complete transfers
- Execute transfers

### Inspections
- Create, complete vehicle inspections
- Inspection history

### Documents
- Manage vehicle documents (registration, insurance, etc.)
- Expiry tracking

### Damages
- Record and track vehicle damages
- Damage history

### Reports
- Fleet utilization reports
- Maintenance reports
- Damage/inspection reports

---

## 5. STAFF (Rental Agent)
**Portal:** `/staff/login`
**Scope:** Single assigned branch only

### Dashboard
- Branch dashboard (read-only KPIs)
- Today's bookings

### Booking Management
- View branch bookings
- Check-in/check-out (rentals)
- Confirm, reject bookings

### Payment Management
- View branch payments
- Confirm cash payments

### Customer Management
- View branch customers

### Vehicle Management
- View branch vehicles (read-only)

### Maintenance
- View maintenance requests
- Create maintenance requests

### Transfers
- Prepare release, mark in-transit, receive vehicles

### Notifications
- Branch notifications

---

## 6. CUSTOMER
**Portal:** `/login` (public portal)
**Scope:** Own data only

### Public Pages
- Browse vehicles (with availability checking)
- View vehicle details
- Contact page

### Authentication
- Register, login, logout
- Password reset
- Profile management

### Bookings
- Check availability
- Price estimates
- Create bookings
- View own bookings
- Cancel bookings
- Booking details

### Payments
- Initialize payments (Chapa/cash)
- Payment status verification
- Payment history
- Payment callbacks

### Driver License
- Upload driver license (front/back)
- View license status
- Update documents
- License eligibility check for vehicles

### Reviews
- View eligible bookings for review
- Create reviews for completed bookings
- Edit own reviews
- View review responses

### Notifications
- View own notifications
- Mark as read

---

## API Endpoint Access Matrix

| Endpoint Pattern | Admin | Fleet Mgr | Branch Mgr | Staff | Customer |
|-----------------|-------|-----------|------------|-------|----------|
| `/api/admin/**` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `/api/branch/**` | ✅ | ❌ | ✅ (own) | ❌ | ❌ |
| `/api/fleet/**` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `/api/staff/**` | ✅ | ❌ | ❌ | ✅ (own) | ❌ |
| `/api/customer/**` | ❌ | ❌ | ❌ | ❌ | ✅ |
| `/api/vehicles` (GET) | ✅ | ✅ | ✅ (own) | ✅ (own) | ✅ |
| `/api/vehicles` (POST) | ✅ | ✅ | ✅ (own) | ❌ | ❌ |
| `/api/bookings` (customer) | ❌ | ❌ | ❌ | ❌ | ✅ |
| `/api/admin/bookings` | ✅ | ❌ | ✅ (own) | ✅ (own) | ❌ |
| `/api/payments` | ✅ | ❌ | ✅ (own) | ✅ (own) | ✅ (own) |
| `/api/admin/payments/**` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `/api/maintenance` | ✅ | ✅ | ✅ (own) | ✅ (own) | ❌ |
| `/api/vehicle-transfers` | ✅ | ✅ | ✅ (own) | ✅ (own) | ❌ |

---

## Branch Scoping Rules

### For Branch-Scoped Users (Branch Manager, Staff):
- Can only access data where `branch_id = user.branch_id`
- Vehicle creation automatically sets `branch_id = user.branch_id`
- Cannot view or modify other branches' data
- Transfer operations only as source/destination of their branch

### For Company-Wide Users (Admin, Fleet Manager):
- Can access all branches' data
- Can filter by `branch_id` query parameter
- Can create vehicles for any branch (must specify branch_id)

### For Customers:
- No branch scoping
- Can only access own bookings, payments, licenses, reviews
- Vehicle browsing shows all active branches' available vehicles

---

## Status & Workflow Permissions

### Booking Status Transitions
| Status | Admin | Branch Mgr | Staff | Customer |
|--------|-------|------------|-------|----------|
| Create | ✅ | ✅ | ✅ | ✅ |
| Confirm | ✅ | ✅ | ✅ | ❌ |
| Reject | ✅ | ✅ | ✅ | ❌ |
| Prepare Pickup | ✅ | ✅ | ✅ | ❌ |
| Pickup | ✅ | ✅ | ✅ | ❌ |
| Return | ✅ | ✅ | ✅ | ❌ |
| Cancel | ✅ | ✅ | ✅ | ✅ (own) |

### Payment Actions
| Action | Admin | Branch Mgr | Staff | Customer |
|--------|-------|------------|-------|----------|
| Initialize (Chapa) | ✅ | ❌ | ❌ | ✅ (own) |
| Confirm Cash | ✅ | ✅ | ✅ | ❌ |
| Verify | ✅ | ✅ | ✅ | ✅ (own) |
| Mark Failed | ✅ | ✅ | ❌ | ❌ |
| Refund | ✅ | ❌ | ❌ | ❌ |
| Reconcile | ✅ | ❌ | ❌ | ❌ |

### Vehicle Status
| Status | Admin | Fleet Mgr | Branch Mgr | Staff |
|--------|-------|-----------|------------|-------|
| All statuses | ✅ | ✅ | ✅ (own) | ❌ (read-only) |

---

## Special Permissions

### Super Admin
- Bypasses all branch scoping
- Can access any route with `role:admin` middleware
- Inherits admin permissions automatically in RoleMiddleware

### Archive Operations
- Only Admin can archive bookings/payments
- Soft archive (never hard delete financial records)

### License Verification
- Admin, Branch Manager, Staff can approve/reject licenses
- Customers can only submit/view own licenses

### Review Management
- Admin, Branch Manager can moderate reviews
- Customers can only create/edit own reviews

---

## Middleware Reference

```php
// Role middleware usage in routes
->middleware('role:admin,branch_manager,staff')  // Management roles
->middleware('role:admin,fleet_manager,branch_manager')  // Fleet roles
->middleware('role:admin,fleet_manager,branch_manager,staff')  // All management
->middleware('role:branch_manager,admin')  // Branch portal
->middleware('role:admin')  // Admin only
->middleware('role:admin,fleet_manager')  // Fleet + Admin
```

### RoleMiddleware Logic
```php
$allowedRoles = explode(',', $roles);

// super_admin inherits admin access
if (in_array('admin', $allowedRoles) && $user->role === 'super_admin') {
    return $next($request);
}

if (!in_array($user->role, $allowedRoles)) {
    return 403 Forbidden;
}
```

---

## Portal Routing

| Portal | Path Prefix | Middleware | Layout |
|--------|-------------|------------|--------|
| Admin | `/admin/*` | `PortalGate portal=admin` | AdminLayout |
| Branch | `/branch/*` | `PortalGate portal=branch` | BranchLayout |
| Fleet | `/fleet/*` | `PortalGate portal=fleet` | FleetLayout |
| Staff | `/staff/*` | `PortalGate portal=staff` | StaffLayout |
| Customer | `/dashboard/*` | `ProtectedRoute + CustomerLayout` | CustomerLayout |
| Public | `/*` | None | CustomerLayout |

---

## Data Isolation Summary

| Resource | Admin | Fleet Mgr | Branch Mgr | Staff | Customer |
|----------|-------|-----------|------------|-------|----------|
| Companies | All | None | None | None | None |
| Branches | All | All | Own only | Own only | Active only |
| Users | All | None | Own branch | Own branch | Self only |
| Vehicles | All | All | Own branch | Own branch | Available |
| Bookings | All | None | Own branch | Own branch | Own only |
| Payments | All | None | Own branch | Own branch | Own only |
| Maintenance | All | All | Own branch | Own branch | None |
| Reviews | All | None | Own branch | Own branch | Own only |
| Licenses | All | None | Own branch | Own branch | Own only |
| Transfers | All | All | Own branch | Own branch | None |

---

## Default Credentials (Development)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@carrental.com | password |
| Fleet Manager | fleet@carrental.com | password |
| Branch Manager (Main) | mai.1.manager@apexrentals.com | password |
| Branch Manager (Airport) | air.2.manager@apexrentals.com | password |
| Branch Manager (Downtown) | dow.3.manager@apexrentals.com | password |
| Branch Manager (Shopping Mall) | sho.4.manager@apexrentals.com | password |
| Staff (Main) | staff@carrental.com | password |
| Staff (Airport) | staffairport@carrental.com | password |
| Customer | kine@gmail.com | password |

**All passwords:** `password`