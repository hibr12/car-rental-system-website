# Backend Team Member 1: Foundation, Authentication & Vehicle Management

You are a senior Laravel backend developer working on a shared professional Car Rental System backend.

The project uses:

* PHP 8.2+
* Laravel 12+
* PostgreSQL
* Laravel Sanctum
* Eloquent ORM
* RESTful API architecture
* Form Requests
* API Resources
* Policies and Gates
* PHPUnit or Pest

Your branch is:

feature/auth-vehicles

Before making changes, inspect the existing project structure and understand the current code. Do not delete working code or create duplicate models, migrations, routes, or files.

Your responsibility is to implement the backend foundation, authentication, authorization, users, vehicle categories, vehicles, vehicle images, search, filtering, sorting, pagination, factories, seeders, and related tests.

## 1. Project Foundation

Verify and configure:

* PostgreSQL database
* Laravel Sanctum
* API-based architecture
* `.env.example`
* `.gitignore`
* CORS
* API route structure
* Secure environment variables

The backend must be completely separated from the future React frontend.

## 2. Users

Create or improve the users system with:

* id
* name
* email
* password
* phone
* profile_photo
* role
* email_verified_at
* timestamps

Roles:

* customer
* admin
* fleet_manager
* staff

Use secure password hashing.

Create proper relationships and role-based authorization.

## 3. Authentication API

Implement:

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
PUT  /api/auth/profile
```

Use Laravel Sanctum.

Implement:

* Registration
* Login
* Logout
* Current authenticated user
* Profile update
* Secure token handling
* Validation
* Consistent API responses

Use Form Requests such as:

* RegisterRequest
* LoginRequest
* UpdateProfileRequest

Use API Resources such as:

* UserResource

Never expose passwords or password hashes.

## 4. Authorization

Implement:

* Role middleware
* Policies
* Gates where appropriate

Permissions:

### Customer

* Browse vehicles
* View vehicle details
* View categories
* Manage personal profile

### Admin

* Complete system access

### Fleet Manager

* Create vehicles
* Update vehicles
* Manage vehicle availability

### Staff

* View operational information according to their permissions

Ensure unauthorized users receive HTTP 403 responses.

## 5. Vehicle Categories

Create a normalized category system.

Fields:

* id
* name
* slug
* description
* timestamps

Example categories:

* Economy
* Sedan
* SUV
* Luxury
* Sports
* Electric
* Van

API:

```text
GET    /api/categories
GET    /api/categories/{category}
POST   /api/categories
PUT    /api/categories/{category}
DELETE /api/categories/{category}
```

Only authorized users should create, update, or delete categories.

## 6. Vehicles

Create the vehicles system with:

* id
* category_id
* brand
* model
* year
* registration_number
* vin_number
* description
* fuel_type
* transmission
* seats
* color
* mileage
* purchase_price
* rental_price_per_day
* status
* featured
* location
* timestamps

Statuses:

* available
* rented
* reserved
* maintenance
* unavailable

Create proper relationships between:

* Category
* Vehicle
* Vehicle Images

## 7. Vehicle Images

A vehicle can have multiple images.

Fields:

* id
* vehicle_id
* image_url
* is_primary
* timestamps

Implement proper relationships and API output.

## 8. Vehicle API

Implement:

```text
GET    /api/vehicles
GET    /api/vehicles/{vehicle}
POST   /api/vehicles
PUT    /api/vehicles/{vehicle}
DELETE /api/vehicles/{vehicle}
```

Public users should be able to browse available vehicles.

Only authorized administrators and fleet managers can create or modify vehicles.

## 9. Search, Filtering, Sorting, Pagination

Support:

```text
/api/vehicles?search=Toyota
/api/vehicles?category=suv
/api/vehicles?min_price=50
/api/vehicles?max_price=300
/api/vehicles?fuel_type=hybrid
/api/vehicles?transmission=automatic
/api/vehicles?status=available
/api/vehicles?featured=true
/api/vehicles?sort=price_asc
/api/vehicles?page=1
```

Implement:

* Search
* Filtering
* Sorting
* Pagination

Use efficient database queries and avoid loading unnecessary records into memory.

## 10. Validation

Use Form Request classes:

* StoreVehicleRequest
* UpdateVehicleRequest
* StoreCategoryRequest
* UpdateCategoryRequest

Validate all input.

Do not place large validation logic inside controllers.

## 11. API Resources

Create:

* UserResource
* CategoryResource
* VehicleResource
* VehicleImageResource if appropriate

Do not expose raw database models unnecessarily.

Use consistent responses:

```json
{
  "success": true,
  "message": "Vehicles retrieved successfully",
  "data": []
}
```

## 12. Factories and Seeders

Create realistic factories and seeders for:

* Admin user
* Staff user
* Fleet manager
* Customer users
* Vehicle categories
* At least 20 realistic vehicles
* Vehicle images

Make the data useful for future frontend development.

## 13. Tests

Create tests for:

### Authentication

* User can register
* User can log in
* Invalid credentials fail
* User can log out

### Authorization

* Customer cannot access admin operations
* Fleet manager can manage vehicles
* Unauthorized users cannot create vehicles

### Vehicles

* Anyone can view vehicles
* Authorized users can create vehicles
* Validation works
* Filtering works
* Pagination works

## Important Rules

* Follow Laravel conventions.
* Keep controllers thin.
* Use services when business logic becomes complex.
* Use policies for authorization.
* Use Form Requests for validation.
* Use API Resources.
* Use eager loading where appropriate.
* Add indexes where useful.
* Do not create duplicate migrations or models.
* Do not delete working code.
* Do not implement booking, payment, review, maintenance, or dashboard features because those belong to other team members.

Before finishing:

1. Run migrations.
2. Run seeders.
3. Run tests.
4. Check routes.
5. Check authentication.
6. Check authorization.
7. Check vehicle APIs.
8. Fix errors.

Finally, provide a summary of all files changed and explain how the other team members can use the users, roles, categories, and vehicles created by this part.


# Backend Team Member 2: Bookings, Payments & Reviews

You are a senior Laravel backend developer working on a shared professional Car Rental System backend.

The project uses:

* PHP 8.2+
* Laravel 12+
* PostgreSQL
* Laravel Sanctum
* Eloquent ORM
* RESTful API architecture
* Form Requests
* API Resources
* Policies
* Services
* PHPUnit or Pest

Your branch is:

feature/bookings-payments-reviews

Before making changes, inspect the existing project and understand the work already implemented by the foundation and vehicle team.

Do not delete working code or create duplicate models, migrations, routes, or files.

Your responsibility is to implement bookings, booking business logic, payments, reviews, ratings, related notifications, and tests.

## 1. Booking Database

Create a bookings table with:

* id
* booking_reference
* user_id
* vehicle_id
* pickup_location
* return_location
* pickup_date
* return_date
* number_of_days
* price_per_day
* subtotal
* additional_charges
* discount
* total_price
* status
* payment_status
* notes
* timestamps

Booking statuses:

* pending
* confirmed
* active
* completed
* cancelled
* rejected

Payment statuses:

* unpaid
* pending
* paid
* failed
* refunded

Generate a unique booking reference automatically.

Create relationships with:

* User
* Vehicle
* Payments
* Reviews

## 2. Booking Business Logic

Create a service such as:

```text
app/Services/BookingService.php
```

Before creating a booking:

1. Verify the vehicle exists.
2. Verify the vehicle is not under maintenance.
3. Verify the vehicle is not unavailable.
4. Verify the vehicle is not already booked for the requested dates.
5. Validate that the return date is after the pickup date.
6. Calculate the number of rental days.
7. Calculate the rental price.
8. Calculate the final total.
9. Prevent overlapping bookings.

Example:

```text
pickup_date: 2026-08-01
return_date: 2026-08-05
number_of_days: 4
price_per_day: 100
total_price: 400
```

Never trust prices or totals sent by the frontend.

The backend must calculate all prices securely.

Use database transactions for important booking operations.

## 3. Booking API

Customer endpoints:

```text
POST /api/bookings
GET  /api/bookings
GET  /api/bookings/{booking}
PUT  /api/bookings/{booking}/cancel
```

Admin and staff endpoints:

```text
GET /api/admin/bookings
PUT /api/admin/bookings/{booking}/confirm
PUT /api/admin/bookings/{booking}/reject
PUT /api/admin/bookings/{booking}/pickup
PUT /api/admin/bookings/{booking}/return
```

Implement proper authorization.

Customers must only access their own bookings.

Admins and authorized staff can manage operational booking actions.

## 4. Booking Validation

Create Form Requests:

* StoreBookingRequest
* CancelBookingRequest if needed

Validate:

* Vehicle ID
* Pickup location
* Return location
* Pickup date
* Return date
* Notes

The backend must perform all important business validation.

## 5. Booking API Resource

Create:

```text
BookingResource
```

Return clean JSON data including:

* Booking information
* Vehicle information
* Customer information where authorized
* Payment status
* Booking status

Do not expose unnecessary database fields.

## 6. Payments

Create a payments table:

* id
* booking_id
* user_id
* amount
* payment_method
* transaction_reference
* status
* paid_at
* timestamps

Payment methods:

* cash
* bank_transfer
* card
* online_payment

Payment statuses:

* pending
* paid
* failed
* refunded

Create:

```text
app/Services/PaymentService.php
```

Build a clean architecture that can later integrate with a real payment gateway.

Never store:

* Raw card numbers
* CVV
* Private payment credentials

API:

```text
GET  /api/payments
POST /api/payments
GET  /api/payments/{payment}
```

Users should only see their own payment records unless authorized administrators need broader access.

Create:

```text
PaymentResource
```

## 7. Reviews and Ratings

Create a reviews table:

* id
* user_id
* vehicle_id
* booking_id
* rating
* comment
* status
* timestamps

Rules:

* Rating must be between 1 and 5.
* Only customers with completed bookings can review.
* A customer cannot review the same booking multiple times.
* Administrators can moderate or delete reviews.

API:

```text
GET    /api/vehicles/{vehicle}/reviews
POST   /api/vehicles/{vehicle}/reviews
DELETE /api/reviews/{review}
```

Create:

```text
StoreReviewRequest
ReviewResource
```

## 8. Notifications

Implement notifications for:

* Successful booking
* Booking confirmation
* Booking cancellation
* Payment success
* Payment failure
* Booking reminders

Use Laravel Notifications.

Support database and email notifications where appropriate.

Do not create frontend UI.

## 9. Tests

Create tests for:

### Bookings

* Customer can create a booking.
* Invalid dates are rejected.
* Return date must be after pickup date.
* Overlapping bookings are rejected.
* Vehicle availability is checked.
* Vehicle under maintenance cannot be booked.
* Total price is calculated by the backend.
* Customer can cancel an eligible booking.

### Authorization

* Customer cannot access another user's booking.
* Unauthorized users cannot manage bookings.
* Staff can perform authorized operational actions.

### Payments

* Payment records can be created.
* Payment amount is validated.
* Users cannot access unauthorized payment records.

### Reviews

* Completed customers can review vehicles.
* Incomplete bookings cannot create reviews.
* Duplicate reviews are rejected.
* Ratings outside 1–5 are rejected.

## Important Rules

* Follow existing models and relationships from Part 1.
* Reuse the existing User and Vehicle models.
* Do not create duplicate User or Vehicle models.
* Do not modify unrelated features unnecessarily.
* Keep controllers thin.
* Use services for complex business logic.
* Use Form Requests.
* Use API Resources.
* Use Policies.
* Use database transactions.
* Use consistent API responses.

Before finishing:

1. Run migrations.
2. Run tests.
3. Test booking creation.
4. Test overlapping booking prevention.
5. Test price calculation.
6. Test payment APIs.
7. Test review rules.
8. Check authorization.
9. Fix all errors.

Finally, provide a summary of all files changed and explain the API endpoints implemented.


# Backend Team Member 3: Admin, Maintenance, Contact, Dashboard & Documentation

You are a senior Laravel backend developer working on a shared professional Car Rental System backend.

The project uses:

* PHP 8.2+
* Laravel 12+
* PostgreSQL
* Laravel Sanctum
* Eloquent ORM
* RESTful API architecture
* Form Requests
* API Resources
* Policies
* Services
* Notifications
* PHPUnit or Pest

Your branch is:

feature/admin-maintenance

Before making changes, inspect the existing project and understand the features already implemented by the other team members.

Do not delete working code or create duplicate models, migrations, routes, or files.

Your responsibility is to implement administrative management, fleet management, staff operations, maintenance, contact messages, dashboard statistics, reports, and project documentation.

## 1. Admin Authorization

Use the existing roles:

* admin
* fleet_manager
* staff
* customer

Use Laravel Policies and middleware.

Admin:

* Full system access

Fleet Manager:

* Manage vehicles
* Manage availability
* Manage maintenance

Staff:

* Manage operational rental activities
* View and update authorized bookings

Customers:

* Must not access admin endpoints

## 2. Admin User Management

Implement APIs for administrators to:

* View users
* View user details
* Update user information
* Change user roles where appropriate
* Disable or manage accounts according to project rules

Protect all admin endpoints with authorization.

## 3. Admin Booking Management

Use the existing booking system created by Part 2.

Implement or complete:

```text
GET /api/admin/bookings
PUT /api/admin/bookings/{booking}/confirm
PUT /api/admin/bookings/{booking}/reject
PUT /api/admin/bookings/{booking}/pickup
PUT /api/admin/bookings/{booking}/return
```

Ensure:

* Correct status transitions
* Proper authorization
* Database consistency
* Vehicle status updates where necessary

Do not duplicate the Booking model or BookingService.

## 4. Vehicle Maintenance

Create a maintenance system.

Fields:

* id
* vehicle_id
* title
* description
* maintenance_type
* cost
* start_date
* end_date
* status
* notes
* created_by
* timestamps

Maintenance statuses:

* scheduled
* in_progress
* completed
* cancelled

API:

```text
GET    /api/maintenance
POST   /api/maintenance
PUT    /api/maintenance/{maintenance}
DELETE /api/maintenance/{maintenance}
```

Rules:

* Vehicles under maintenance cannot be booked.
* Starting maintenance should update vehicle availability where appropriate.
* Completing maintenance should allow authorized users to update vehicle status.
* Record who created the maintenance record.

Create:

* Maintenance model
* Maintenance migration
* Maintenance controller
* Maintenance requests
* Maintenance resource
* Maintenance policy

## 5. Contact Messages

Create a contact message system.

Fields:

* id
* name
* email
* phone
* subject
* message
* status
* replied_at
* timestamps

Customers or public users can submit contact messages.

Administrators can:

* View messages
* Mark messages as read
* Mark messages as replied
* Delete messages

Create suitable API routes and validation.

## 6. Admin Dashboard API

Create:

```text
GET /api/admin/dashboard
```

Return:

* Total users
* Total customers
* Total vehicles
* Available vehicles
* Rented vehicles
* Vehicles under maintenance
* Total bookings
* Pending bookings
* Active rentals
* Completed rentals
* Cancelled bookings
* Total revenue
* Monthly revenue
* Recent bookings
* Recent users
* Popular vehicles

Create:

```text
app/Services/DashboardService.php
```

Return a clean JSON structure suitable for a future React admin dashboard.

Use efficient database queries.

Avoid unnecessary repeated queries.

## 7. Reports and Statistics

Implement useful admin reporting data where appropriate:

* Revenue summaries
* Booking summaries
* Vehicle utilization
* Popular vehicles
* Booking status statistics
* Maintenance cost summaries

Keep the implementation efficient and scalable.

## 8. Notifications Integration

Review and integrate the notification system created by Part 2.

Ensure important events can trigger:

* Booking confirmation
* Booking cancellation
* Payment notifications
* Booking reminders
* Vehicle return reminders

Do not create duplicate notification classes.

## 9. Admin and Operational Policies

Create or improve policies for:

* Users
* Vehicles
* Categories
* Bookings
* Payments
* Reviews
* Maintenance
* Contact messages

Ensure:

* Customers cannot access admin endpoints.
* Staff only access staff-authorized operations.
* Fleet managers manage vehicles and maintenance.
* Admins have full access.

## 10. Documentation

Create or update:

```text
README.md
API_DOCUMENTATION.md
```

README must include:

* Project description
* Technologies
* Requirements
* Installation
* Environment configuration
* PostgreSQL setup
* Migration commands
* Seeder commands
* Development commands
* Testing commands
* Deployment instructions

API_DOCUMENTATION.md must document every endpoint with:

* HTTP method
* URL
* Authentication requirement
* Required role
* Parameters
* Request body
* Successful response
* Error response
* Example request

## 11. Deployment Preparation

Verify that the backend is ready for deployment.

Check:

* `.env.example`
* `.gitignore`
* Production configuration
* PostgreSQL configuration
* CORS
* Frontend URL configuration
* API route configuration
* Storage configuration
* Logging
* Error handling

Never commit:

* `.env`
* Passwords
* API keys
* Private tokens
* Database credentials

## 12. Final Integration Testing

After implementing your features, test the integration with:

* Authentication
* Users
* Vehicles
* Categories
* Bookings
* Payments
* Reviews

Verify that your code works with the existing models and services.

Run:

* Migrations
* Seeders
* Feature tests
* Unit tests where appropriate

Check:

* Route conflicts
* Duplicate migrations
* Duplicate models
* Authorization errors
* Database relationship errors
* API response consistency

## Important Rules

* Inspect existing code before changing anything.
* Do not duplicate existing models, migrations, services, or notifications.
* Reuse existing BookingService, PaymentService, User, Vehicle, and Booking models.
* Keep controllers thin.
* Use services for complex logic.
* Use Form Requests.
* Use API Resources.
* Use Policies.
* Use database transactions when necessary.
* Follow Laravel conventions.
* Do not add frontend UI code.

Finally, provide:

1. A complete summary of changes.
2. All files created or modified.
3. Tests performed.
4. Errors fixed.
5. Remaining issues.
6. Instructions for merging this branch into the main branch.
