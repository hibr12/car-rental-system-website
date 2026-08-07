# API Documentation

## Authentication

### Register

- Method: POST
- URL: /api/auth/register
- Authentication: None
- Required role: Public
- Body: name, email, password, password_confirmation, phone

### Login

- Method: POST
- URL: /api/auth/login
- Authentication: None
- Required role: Public
- Body: email, password

### Logout

- Method: POST
- URL: /api/auth/logout
- Authentication: Sanctum
- Required role: Any authenticated user

### Current user

- Method: GET
- URL: /api/auth/me
- Authentication: Sanctum
- Required role: Any authenticated user

### Update profile

- Method: PUT
- URL: /api/auth/profile
- Authentication: Sanctum
- Required role: Any authenticated user

## Categories

- GET /api/categories
- GET /api/categories/{category}
- POST /api/categories
- PUT /api/categories/{category}
- DELETE /api/categories/{category}

## Vehicles

- GET /api/vehicles
- GET /api/vehicles/{vehicle}
- POST /api/vehicles
- PUT /api/vehicles/{vehicle}
- DELETE /api/vehicles/{vehicle}
- GET /api/vehicles/{vehicle}/reviews

## Bookings

- GET /api/bookings
- POST /api/bookings
- GET /api/bookings/{booking}
- PUT /api/bookings/{booking}/cancel

### Admin booking actions

- GET /api/admin/bookings
- PUT /api/admin/bookings/{booking}/confirm
- PUT /api/admin/bookings/{booking}/reject
- PUT /api/admin/bookings/{booking}/pickup
- PUT /api/admin/bookings/{booking}/return

## Payments

- GET /api/payments
- POST /api/payments
- GET /api/payments/{payment}

## Reviews

- POST /api/vehicles/{vehicle}/reviews
- DELETE /api/reviews/{review}

## Maintenance

- GET /api/maintenance
- POST /api/maintenance
- GET /api/maintenance/{maintenance}
- PUT /api/maintenance/{maintenance}
- DELETE /api/maintenance/{maintenance}

## Contact Messages

- POST /api/contact-messages
- GET /api/contact-messages
- PUT /api/contact-messages/{contactMessage}
- DELETE /api/contact-messages/{contactMessage}

## Admin Dashboard

- GET /api/admin/dashboard
- GET /api/admin/users
- GET /api/admin/users/{user}
- PUT /api/admin/users/{user}

## Notes

- Public endpoints allow browsing vehicles and categories.
- Admin and staff endpoints require authentication and the appropriate role-based permissions.
