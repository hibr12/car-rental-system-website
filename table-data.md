## Task: Fully Integrate and Repair the Merged Car Rental Project

You are working on a Laravel + React car rental management system.

I recently merged the `feature/bookings-payments-reviews` branch into `development`. The merge completed successfully with a **fast-forward**, but the merged branch introduced a large number of new backend and frontend features.

The project now needs a complete integration and consistency audit.

### Main features introduced by the merged changes

The project needs to correctly support and integrate:

1. Authentication and authorization
2. Vehicle management
3. Admin management
4. Branch management
5. Branch manager functionality
6. Fleet management
7. Maintenance management
8. Customer booking workflow
9. Payment management
10. Chapa/payment gateway integration
11. Payment verification and reconciliation
12. Driver-license upload and verification
13. Reviews and review management
14. Notifications
15. Vehicle transfers
16. Vehicle inspections
17. Vehicle damage/document management
18. Reports and analytics
19. Archive functionality
20. Frontend dashboards and role-based portals

---

# VERY IMPORTANT: Do NOT blindly modify the project

Before changing anything:

1. Inspect the complete Laravel backend.
2. Inspect the complete React frontend.
3. Inspect `composer.json`.
4. Inspect `package.json`.
5. Inspect all database migrations.
6. Inspect models and relationships.
7. Inspect controllers.
8. Inspect services.
9. Inspect Form Requests.
10. Inspect API resources.
11. Inspect routes.
12. Inspect middleware and policies.
13. Inspect seeders/factories.
14. Inspect frontend API clients.
15. Inspect frontend routes.
16. Inspect authentication/role guards.
17. Inspect payment pages and APIs.
18. Inspect driver-license pages and APIs.
19. Inspect branch-management pages and APIs.
20. Inspect tests.

Do not assume that similarly named migrations or classes are correct.

---

# 1. DATABASE AND MIGRATION AUDIT

This is the highest priority.

The existing database already contains older migrations, while the merged branch introduced newer migrations.

Run:

```bash
php artisan migrate:status
```

Carefully compare all migrations.

Identify:

* duplicate `create_*_table` migrations
* duplicate `add_*` migrations
* migrations that attempt to add columns that already exist
* migrations that attempt to create tables that already exist
* migrations that conflict with earlier migrations
* migrations that depend on another migration incorrectly
* migrations that duplicate functionality under different names

For example, the project currently has duplicate/overlapping branch-related migrations such as:

```text
2026_08_09_000001_create_branches_table
2026_08_11_082904_create_branches_table
```

There are also overlapping migrations involving:

```text
vehicles
bookings
users
```

Do NOT simply delete migrations.

First inspect their contents and determine the intended final database schema.

The final database schema must contain every required feature exactly once.

---

# 2. PRESERVE EXISTING DATA

Do NOT use:

```bash
php artisan migrate:fresh
```

Do NOT delete the existing database.

Do NOT drop existing tables merely to make migrations pass.

The current database may contain development data.

Instead, make the migration history and database schema consistent while preserving existing data.

If a migration is genuinely duplicated, determine the safest solution.

If a newer migration contains additional columns that the old migration does not contain, preserve those additions using a proper migration rather than recreating the table.

---

# 3. FINAL DATABASE REQUIREMENTS

Verify that the database correctly supports at least:

### Users

Users should support the required roles and relationships, including:

* customer
* staff
* admin
* branch manager
* fleet manager where applicable

Verify branch relationships and driver-license relationships.

### Companies

Support:

* company
* branches
* branch managers

### Branches

Support:

* company relationship
* branch information
* manager relationship
* branch status
* branch access control

### Vehicles

Support:

* category
* branch
* status
* license category if required
* documents
* inspections
* damage
* transfers
* maintenance relationships

### Bookings

Support:

* customer
* vehicle
* branch
* booking workflow
* approval
* cancellation
* pickup
* completion
* payment relationship
* driver-license verification where required

### Payments

Support:

* booking
* payment amount
* currency
* gateway
* transaction/reference information
* Chapa
* payment initialization
* payment success
* payment failure
* refund
* verification
* cash payment confirmation
* reconciliation
* payment status

### Driver Licenses

Support:

* customer/user
* license information
* uploaded document/image
* verification status
* approval/rejection
* expiration information

### Reviews

Support:

* customer
* booking
* vehicle
* rating
* review
* admin response
* approval/status

### Notifications

Verify that notifications work correctly for the appropriate users and roles.

---

# 4. MODEL RELATIONSHIPS

Audit all Laravel models.

Verify relationships such as:

```text
Company
 └── Branch
      ├── Users
      ├── Vehicles
      ├── Bookings
      ├── Payments
      └── Maintenance
```

and:

```text
User
 ├── DriverLicense
 ├── Bookings
 ├── Payments
 ├── Reviews
 └── Notifications
```

and:

```text
Booking
 ├── User
 ├── Vehicle
 ├── Payment
 └── Review
```

Make sure:

* foreign keys are correct
* inverse relationships exist
* relationship names are consistent
* nullable relationships are handled correctly
* eager loading does not cause errors
* no relationship points to a non-existing model/column

---

# 5. PAYMENT MANAGEMENT

Fully audit payment functionality.

Verify the complete flow:

```text
Customer
   ↓
Create booking
   ↓
Booking approval/availability
   ↓
Payment initialization
   ↓
Chapa or supported payment method
   ↓
Payment callback/status
   ↓
Payment verification
   ↓
Booking payment status updated
   ↓
Customer receives notification
   ↓
Admin/management sees payment
```

Also verify cash payment if implemented.

Make sure payment status cannot become inconsistent.

Examples:

```text
pending
initialized
successful
failed
refunded
cancelled
```

Use the statuses actually defined by the existing project rather than inventing incompatible statuses.

---

# 6. DRIVER LICENSE UPLOAD

Fully integrate driver-license functionality.

The customer should be able to:

1. Open the driver-license page.
2. Enter required license information.
3. Upload the required document.
4. Submit it.
5. See submission status.
6. Receive validation errors when appropriate.

Management/admin should be able to:

1. View submitted licenses.
2. View uploaded documents.
3. Approve a license.
4. Reject a license.
5. Provide an appropriate rejection reason if supported.
6. See expiration information.
7. Prevent invalid/unverified licenses from being used where verification is required.

Verify:

* backend API
* controller
* service
* request validation
* model
* database
* storage configuration
* API resource
* frontend API client
* frontend page
* admin review page
* authorization/policies

---

# 7. BRANCH MANAGEMENT

Fully integrate branch functionality.

Verify:

* company → branch relationship
* branch manager
* branch access middleware
* branch-scoped data
* branch customers
* branch rentals
* branch maintenance requests
* branch vehicles
* branch bookings
* branch payments

A branch manager must not accidentally access data belonging to another branch.

---

# 8. ROLE-BASED ACCESS

Audit all role middleware and frontend guards.

Verify that:

```text
Customer
Admin
Staff
Branch Manager
Fleet Manager
```

can access only the appropriate pages and APIs.

Check both:

### Backend

* middleware
* policies
* authorization
* branch access

### Frontend

* ProtectedRoute
* RoleRoute
* PortalGate
* role redirects
* dashboard layouts
* navigation/sidebar visibility

Do not rely only on frontend restrictions. Backend authorization must also be enforced.

---

# 9. API AUDIT

Inspect:

```text
backend/routes/api.php
```

Make sure:

* routes are not duplicated
* controllers exist
* methods exist
* middleware is correct
* route parameters are correct
* API naming is consistent
* authentication requirements are correct

Check every major feature:

```text
auth
vehicles
bookings
payments
reviews
branches
maintenance
licenses
fleet
notifications
transfers
documents
inspections
admin
```

---

# 10. FRONTEND API INTEGRATION

Audit every frontend API client.

Check files such as:

```text
frontend/src/api/
```

Make sure the frontend calls the correct backend endpoints.

Verify:

* base URL
* authentication token
* Bearer token handling
* error handling
* request format
* response format
* multipart/form-data for file uploads
* payment callbacks
* license uploads
* branch APIs
* booking APIs
* review APIs

Make sure frontend API functions match the actual Laravel routes and controllers.

---

# 11. FILE UPLOADS

Pay special attention to driver-license uploads and other documents.

Verify:

* Laravel filesystem configuration
* storage disk
* upload validation
* allowed file types
* file size validation
* generated file paths
* secure access
* frontend `FormData`
* API content type
* returned file URLs

Do not expose sensitive uploaded documents unnecessarily.

---

# 12. FRONTEND PAGES

Verify that all newly introduced pages compile and work.

Pay particular attention to:

```text
Customer
BookingDetailPage
BookingReviewPage
CustomerPayments
DriverLicensePage
NotificationsPage

Payment
CheckoutPage
BookingConfirmationPage
PaymentStatusPage

Admin
PaymentsPage
PaymentHistoryPage
PaymentReconciliationPage
ReviewsManagement
LicenseReviewPage
BranchesPage
StaffManagementPage
VehicleTransfersPage

Branch
BranchDashboard
BranchCustomers
BranchRentalsPage
BranchMaintenanceRequests

Fleet
FleetDashboard
FleetVehicles
FleetDamage
FleetDocuments
FleetInspections
FleetMaintenance
FleetReports
```

Fix broken imports, routes, API calls, component references, and role redirects.

---

# 13. DEPENDENCIES

Check:

```bash
composer install
```

and:

```bash
npm install
```

Do not randomly change dependency versions.

If `package-lock.json` or `composer.lock` changed during the merge, make sure the project can install cleanly.

Then run:

```bash
npm run build
```

and make sure there are no frontend compilation errors.

---

# 14. LARAVEL VALIDATION

Run:

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list
```

Then run the appropriate tests:

```bash
php artisan test
```

Fix genuine application problems instead of suppressing errors.

---

# 15. DATABASE MIGRATION VALIDATION

After correcting the migration system, run:

```bash
php artisan migrate
```

It must complete without:

```text
Duplicate table
Duplicate column
Undefined column
Foreign key violation
Relation already exists
```

Then run:

```bash
php artisan migrate:status
```

There should be no unexpected pending migrations.

---

# 16. DO NOT HIDE ERRORS

Do not solve problems by:

* deleting the database
* using `migrate:fresh`
* commenting out migrations
* disabling authentication
* disabling authorization
* removing validation
* removing foreign keys
* removing features
* hardcoding fake API responses
* ignoring failing tests

The goal is a genuinely integrated application.

---

# 17. FINAL VERIFICATION

After making the corrections, verify this complete flow:

### Customer

```text
Register/Login
   ↓
Browse vehicles
   ↓
Select vehicle
   ↓
Upload/verify driver license
   ↓
Create booking
   ↓
Booking approval/workflow
   ↓
Make payment
   ↓
Payment verification
   ↓
Booking confirmation
   ↓
Pickup/completion
   ↓
Leave review
```

### Management

```text
Login
   ↓
Dashboard
   ↓
Manage branches
   ↓
Manage staff/managers
   ↓
Manage vehicles
   ↓
Review driver licenses
   ↓
Manage bookings
   ↓
Manage payments
   ↓
Review/reconcile payments
   ↓
Manage reviews
   ↓
Manage maintenance/fleet
   ↓
View notifications/reports
```

### Final requirement

Do not just make the application compile.

The final result must have:

* one consistent database schema
* one consistent migration history
* correct model relationships
* correct API routes
* correct authorization
* correct branch isolation
* working payment flow
* working driver-license upload and verification
* working booking workflow
* working review workflow
* working frontend/backend integration
* no duplicate migrations causing schema errors
* no broken imports
* no obvious runtime errors

At the end, provide a concise report containing:

1. Problems discovered
2. Duplicate/overlapping migrations found
3. Migrations changed and why
4. Database changes made
5. Backend changes made
6. Frontend changes made
7. Payment integration status
8. Driver-license integration status
9. Branch-management integration status
10. Tests executed
11. Remaining issues, if any

**Do not make destructive database changes without explicitly identifying the reason and safer alternative first.**
