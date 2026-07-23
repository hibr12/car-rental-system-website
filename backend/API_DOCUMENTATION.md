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

## Vehicles

### List vehicles

- Method: GET
- URL: /api/vehicles
- Authentication: None
- Required role: Public
- Query params: search, category, min_price, max_price, fuel_type, transmission, status, featured, sort, page

### Create vehicle

- Method: POST
- URL: /api/vehicles
- Authentication: Sanctum
- Required role: admin or fleet_manager

## Bookings

### Create booking

- Method: POST
- URL: /api/bookings
- Authentication: Sanctum
- Required role: customer

### Admin booking actions

- Method: PUT
- URL: /api/admin/bookings/{booking}/confirm|reject|pickup|return
- Authentication: Sanctum
- Required role: admin or staff

## Maintenance

### List maintenance records

- Method: GET
- URL: /api/maintenance
- Authentication: Sanctum
- Required role: admin, fleet_manager, or staff

### Create maintenance record

- Method: POST
- URL: /api/maintenance
- Authentication: Sanctum
- Required role: admin or fleet_manager

## Contact messages

### Submit contact message

- Method: POST
- URL: /api/contact-messages
- Authentication: None
- Required role: Public

### Admin manage contact messages

- Method: PUT /api/contact-messages/{message}
- Authentication: Sanctum
- Required role: admin
