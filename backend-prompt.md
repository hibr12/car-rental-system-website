# Complete Prompt: Build a Production-Ready Car Rental System Backend

You are a senior Laravel backend architect and developer.

I am building a complete car rental system website. The frontend has not been created yet, so you must build the backend independently using a clean, well-documented REST API architecture. The future frontend will be built with React and will consume this API.

Do not make assumptions about the visual appearance of the frontend. Your responsibility is to build a robust, scalable, secure, well-structured backend that provides all the data and functionality required by a modern car rental system.

---

# 1. Technology Stack

Use the following technologies:

* PHP 8.2+
* Laravel 12+
* PostgreSQL
* Laravel Sanctum for API authentication
* RESTful API architecture
* Eloquent ORM
* Form Request validation
* API Resources
* Policies and Gates for authorization
* Laravel Notifications
* Laravel Queues where appropriate
* PHPUnit or Pest for testing
* Laravel's built-in logging system

The backend must be completely API-based.

The frontend and backend must be separated.

The backend must never contain frontend UI code.

---

# 2. Project Objective

Build a complete car rental management system for a professional car rental company.

The system must allow:

* Customers to browse available vehicles
* Customers to search and filter vehicles
* Customers to register and log in
* Customers to make rental bookings
* Customers to view and manage their bookings
* Customers to cancel bookings according to business rules
* Customers to make payments or have payment records
* Customers to leave vehicle reviews
* Administrators to manage the entire system
* Fleet managers to manage vehicles and maintenance
* Staff members to manage rental operations

The system must be designed so that the future React frontend can easily consume the API.

---

# 3. User Roles

Implement the following roles:

## Customer

Permissions:

* Register
* Login
* Logout
* View profile
* Update profile
* Browse vehicles
* Search vehicles
* Filter vehicles
* View vehicle details
* Create bookings
* View personal bookings
* Cancel eligible bookings
* View payment information
* Leave reviews for completed rentals

## Admin

The administrator has complete access to the system.

Permissions:

* Manage users
* Manage roles
* Manage vehicles
* Manage vehicle categories
* Manage bookings
* Manage payments
* Manage reviews
* Manage maintenance records
* View dashboard statistics
* View reports
* Manage system settings
* View visitor analytics

## Fleet Manager

Permissions:

* Create vehicles
* Update vehicles
* Update vehicle availability
* Manage vehicle maintenance
* View vehicle history
* Mark vehicles as available, rented, unavailable, or under maintenance

## Staff

Permissions:

* View bookings
* Confirm vehicle pickup
* Confirm vehicle return
* Update booking status
* Manage customer rental operations

Use Laravel Policies and middleware to enforce role-based access control.

---

# 4. Database Design

Design a normalized PostgreSQL database.

Create proper migrations, foreign keys, indexes, timestamps, and appropriate constraints.

The database should include at least the following entities.

---

## Users

Fields:

* id
* name
* email
* password
* phone
* profile_photo
* role
* email_verified_at
* created_at
* updated_at

Roles:

* customer
* admin
* fleet_manager
* staff

Use secure password hashing.

---

## Vehicle Categories

Fields:

* id
* name
* slug
* description
* created_at
* updated_at

Examples:

* Economy
* Sedan
* SUV
* Luxury
* Sports
* Electric
* Van

---

## Vehicles

Fields:

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
* created_at
* updated_at

Vehicle statuses:

* available
* rented
* reserved
* maintenance
* unavailable

Create a proper relationship between vehicles and categories.

---

## Vehicle Images

A vehicle can have multiple images.

Fields:

* id
* vehicle_id
* image_url
* is_primary
* created_at
* updated_at

---

## Bookings

Fields:

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
* created_at
* updated_at

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

---

# 5. Booking Business Rules

Implement proper business logic.

Before creating a booking:

1. Verify that the vehicle exists.
2. Verify that the vehicle is not under maintenance.
3. Verify that the vehicle is not unavailable.
4. Check whether the vehicle is already booked during the requested dates.
5. Validate that the return date is after the pickup date.
6. Calculate the number of rental days.
7. Calculate the total rental price.
8. Prevent overlapping bookings.

Example:

```text
pickup_date: 2026-08-01
return_date: 2026-08-05
number_of_days: 4
price_per_day: 100
total_price: 400
```

The backend must calculate prices securely.

Never trust the total price sent by the frontend.

---

# 6. Payments

Create a payment system architecture.

Create a payments table with:

* id
* booking_id
* user_id
* amount
* payment_method
* transaction_reference
* status
* paid_at
* created_at
* updated_at

Payment methods may include:

* cash
* bank_transfer
* card
* online_payment

For now, create a clean payment architecture that can later integrate with a real payment gateway.

Never store raw card numbers, CVV codes, or sensitive payment credentials.

---

# 7. Reviews and Ratings

Customers can review vehicles after completing a booking.

Fields:

* id
* user_id
* vehicle_id
* booking_id
* rating
* comment
* status
* created_at
* updated_at

Rules:

* Rating must be between 1 and 5.
* Only customers who completed a booking can review the vehicle.
* A customer cannot review the same booking multiple times.
* Administrators can moderate or delete reviews.

---

# 8. Vehicle Maintenance

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
* created_at
* updated_at

Maintenance statuses:

* scheduled
* in_progress
* completed
* cancelled

When a vehicle is under maintenance, it must not be available for booking.

---

# 9. Contact Messages

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
* created_at
* updated_at

Customers can submit contact messages.

Administrators can:

* View messages
* Mark messages as read
* Mark messages as replied
* Delete messages

---

# 10. Notifications

Implement notifications for important events.

Examples:

* Successful registration
* Successful booking
* Booking confirmation
* Booking cancellation
* Payment success
* Payment failure
* Booking reminder
* Vehicle return reminder

Use Laravel Notifications.

Design the notification system so that email and database notifications can be supported.

---

# 11. Admin Dashboard API

Create an admin dashboard endpoint.

Example:

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

Return the data in a clean JSON structure suitable for a React dashboard.

---

# 12. API Routes

Organize routes properly.

## Authentication

```text
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
PUT    /api/auth/profile
```

## Vehicles

```text
GET     /api/vehicles
GET     /api/vehicles/{vehicle}
POST    /api/vehicles
PUT     /api/vehicles/{vehicle}
DELETE  /api/vehicles/{vehicle}
```

Public users should be able to view available vehicles.

Only authorized administrators and fleet managers can create, update, or delete vehicles.

## Categories

```text
GET     /api/categories
GET     /api/categories/{category}
POST    /api/categories
PUT     /api/categories/{category}
DELETE  /api/categories/{category}
```

## Bookings

```text
POST    /api/bookings
GET     /api/bookings
GET     /api/bookings/{booking}
PUT     /api/bookings/{booking}/cancel
```

Admin and staff:

```text
GET     /api/admin/bookings
PUT     /api/admin/bookings/{booking}/confirm
PUT     /api/admin/bookings/{booking}/reject
PUT     /api/admin/bookings/{booking}/pickup
PUT     /api/admin/bookings/{booking}/return
```

## Reviews

```text
GET     /api/vehicles/{vehicle}/reviews
POST    /api/vehicles/{vehicle}/reviews
DELETE  /api/reviews/{review}
```

## Maintenance

```text
GET     /api/maintenance
POST    /api/maintenance
PUT     /api/maintenance/{maintenance}
DELETE  /api/maintenance/{maintenance}
```

## Payments

```text
GET     /api/payments
POST    /api/payments
GET     /api/payments/{payment}
```

## Admin Dashboard

```text
GET /api/admin/dashboard
```

---

# 13. API Response Format

Use consistent JSON responses.

Successful response:

```json
{
  "success": true,
  "message": "Vehicles retrieved successfully",
  "data": []
}
```

Error response:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

Use correct HTTP status codes:

* 200 for successful requests
* 201 for created resources
* 400 for bad requests
* 401 for unauthenticated users
* 403 for unauthorized users
* 404 for resources not found
* 422 for validation errors
* 500 for server errors

---

# 14. Validation

Use Laravel Form Request classes.

Do not put large validation logic directly inside controllers.

Example:

```text
StoreVehicleRequest
UpdateVehicleRequest
StoreBookingRequest
LoginRequest
RegisterRequest
StoreReviewRequest
```

Validate all user input.

Never trust data from the frontend.

---

# 15. Controllers

Use thin controllers.

Controllers should:

1. Receive the request.
2. Validate the data.
3. Call the appropriate business logic.
4. Return a consistent API response.

Move complex business logic into service classes.

Example:

```text
app/Services/
├── BookingService.php
├── VehicleService.php
├── PaymentService.php
└── DashboardService.php
```

---

# 16. API Resources

Use Laravel API Resources to control JSON output.

Create resources such as:

```text
VehicleResource
BookingResource
UserResource
CategoryResource
ReviewResource
PaymentResource
MaintenanceResource
```

Never return raw database models directly when a clean API response is required.

---

# 17. Security Requirements

Implement:

* Password hashing
* Laravel Sanctum authentication
* Authorization policies
* Role-based access control
* Request validation
* Rate limiting
* CORS configuration
* SQL injection protection through Eloquent
* Mass assignment protection
* Secure error handling
* Secure environment variables

Never expose:

* Passwords
* Password hashes
* Private tokens
* API keys
* Payment credentials

Never commit the `.env` file to GitHub.

---

# 18. Search, Filtering, Sorting, and Pagination

The vehicles endpoint must support:

```text
GET /api/vehicles
```

Query parameters:

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

Do not load thousands of records into memory unnecessarily.

---

# 19. Database Seeders and Factories

Create realistic seed data.

Create:

* Admin user
* Staff user
* Fleet manager
* Customer users
* Vehicle categories
* At least 20 realistic vehicles
* Vehicle images
* Sample bookings
* Sample reviews

Create factories for testing.

The seed data must be realistic and useful for frontend development.

---

# 20. Testing

Create tests for:

## Authentication

* User can register
* User can log in
* Invalid credentials fail
* User can log out

## Vehicles

* Anyone can view vehicles
* Unauthorized users cannot create vehicles
* Authorized users can create vehicles
* Vehicle validation works

## Bookings

* Customer can create a booking
* Invalid dates are rejected
* Overlapping bookings are rejected
* Vehicle availability is checked
* Total price is calculated by the backend

## Authorization

* Customer cannot access admin endpoints
* Staff cannot perform admin-only actions
* Fleet managers can manage vehicles
* Admin has complete access

---

# 21. Documentation

Create a complete API documentation file:

```text
API_DOCUMENTATION.md
```

Document every endpoint with:

* HTTP method
* URL
* Authentication requirement
* Required role
* Request parameters
* Request body
* Successful response
* Error response
* Example request

Also create:

```text
README.md
```

Include:

* Project description
* Requirements
* Installation instructions
* Environment setup
* PostgreSQL setup
* Migration commands
* Seeder commands
* Running the development server
* Testing commands
* API documentation
* Deployment instructions

---

# 22. Environment Configuration

Create a proper `.env.example`.

Include:

```env
APP_NAME=CarRentalSystem
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=car_rental
DB_USERNAME=postgres
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```

Do not commit the real `.env` file.

---

# 23. Folder Structure

Use a clean Laravel structure.

Organize the code approximately like this:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Admin/
│   │   ├── Customer/
│   │   ├── FleetManager/
│   │   └── Staff/
│   │
│   ├── Requests/
│   └── Resources/
│
├── Models/
│
├── Policies/
│
├── Services/
│
└── Notifications/

database/
├── factories/
├── migrations/
└── seeders/

routes/
├── api.php
└── web.php

tests/
├── Feature/
└── Unit/
```

Use Laravel conventions whenever possible.

Do not over-engineer the project unnecessarily.

---

# 24. Important Development Rules

Follow these rules:

1. First inspect the existing project structure.
2. Do not delete existing working code without a clear reason.
3. Do not create duplicate models or duplicate migrations.
4. Do not create unnecessary files.
5. Follow Laravel conventions.
6. Keep controllers clean and small.
7. Use services for complex business logic.
8. Use Form Requests for validation.
9. Use API Resources for responses.
10. Use Policies for authorization.
11. Use database transactions for important operations.
12. Use eager loading to avoid N+1 query problems.
13. Add database indexes where appropriate.
14. Write tests for important business logic.
15. Keep the API consistent.
16. Make the backend easy for a future React frontend to consume.
17. Explain important architectural decisions.
18. If something is unclear, inspect the existing code before making assumptions.

---

# 25. Development Order

Build the project in this exact order:

## Phase 1

Analyze the existing project and create the backend architecture.

## Phase 2

Configure PostgreSQL.

## Phase 3

Create database migrations.

## Phase 4

Create models and relationships.

## Phase 5

Create factories and seeders.

## Phase 6

Implement authentication.

## Phase 7

Implement roles and authorization.

## Phase 8

Implement vehicle management.

## Phase 9

Implement vehicle categories and images.

## Phase 10

Implement booking management and booking business logic.

## Phase 11

Implement payment records.

## Phase 12

Implement reviews and ratings.

## Phase 13

Implement maintenance management.

## Phase 14

Implement admin dashboard statistics.

## Phase 15

Implement notifications.

## Phase 16

Implement contact messages.

## Phase 17

Implement search, filtering, sorting, and pagination.

## Phase 18

Write tests.

## Phase 19

Create API documentation.

## Phase 20

Prepare the backend for deployment.

---

# Final Requirement

Do not simply generate random code.

Build this as a real professional backend project.

Before implementing each major feature:

1. Explain the purpose of the feature.
2. Explain the database changes.
3. Explain the API routes.
4. Implement the code.
5. Run or describe the required tests.
6. Check for errors.
7. Explain how the future React frontend will consume the API.

The final result must be a complete, secure, maintainable, scalable Laravel REST API for a professional car rental system.
