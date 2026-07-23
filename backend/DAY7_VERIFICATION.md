# Day 7 — Member 2 Verification & Testing

## Overview

This document provides comprehensive verification steps for all Member 2 responsibilities:

- **Week 1**: Database & Models
- **Week 2**: CRUD APIs

---

## Pre-Requisites

1. Run migrations: `php artisan migrate:fresh --seed`
2. Start the server: `php artisan serve`
3. Use an API client (Postman, Insomnia, or curl)

---

## 1. Authentication Testing

Before testing Member 2 APIs, you need authenticated users.

### Register Users

```bash
# Register Admin
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin User","email":"admin@test.com","password":"password","phone":"1234567890","role":"admin"}'

# Register Customer
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Customer User","email":"customer@test.com","password":"password","phone":"0987654321","role":"customer"}'

# Register Fleet Manager
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Fleet Manager","email":"fleet@test.com","password":"password","phone":"1122334455","role":"fleet_manager"}'

# Register Staff
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Staff User","email":"staff@test.com","password":"password","phone":"5566778899","role":"staff"}'
```

### Login & Get Tokens

```bash
# Login as Admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}'

# Login as Customer
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"customer@test.com","password":"password"}'

# Login as Fleet Manager
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"fleet@test.com","password":"password"}'

# Login as Staff
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"staff@test.com","password":"password"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "1|abc123..."
  }
}
```

---

## 2. Vehicle Module Testing

### 2.1 Create Vehicle (Admin/Fleet Manager)

```bash
# Create Vehicle (as Admin)
curl -X POST http://localhost:8000/api/vehicles \
  -H "Authorization: Bearer {ADMIN_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 1,
    "brand": "Toyota",
    "model": "Camry",
    "year": 2024,
    "registration_number": "ABC-1234",
    "vin_number": "1HGBH41JXMN109186",
    "description": "Reliable sedan for daily rental",
    "fuel_type": "petrol",
    "transmission": "automatic",
    "seats": 5,
    "color": "White",
    "mileage": 15000,
    "purchase_price": 25000,
    "rental_price_per_day": 50,
    "status": "available",
    "featured": true,
    "location": "Main Branch",
    "images": [
      {"image_url": "https://example.com/camry1.jpg", "is_primary": true},
      {"image_url": "https://example.com/camry2.jpg", "is_primary": false}
    ]
  }'
```

**Expected Response (201):**
```json
{
  "success": true,
  "message": "Vehicle created successfully",
  "data": {
    "id": 1,
    "brand": "Toyota",
    "model": "Camry",
    ...
  }
}
```

### 2.2 View All Vehicles (Public)

```bash
curl -X GET http://localhost:8000/api/vehicles
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Vehicles retrieved successfully",
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 12,
    "total": 1
  }
}
```

### 2.3 View Vehicle Details (Public)

```bash
curl -X GET http://localhost:8000/api/vehicles/1
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Vehicle retrieved successfully",
  "data": {
    "id": 1,
    "brand": "Toyota",
    "model": "Camry",
    "category": {...},
    "images": [...]
  }
}
```

### 2.4 Update Vehicle (Admin/Fleet Manager)

```bash
curl -X PUT http://localhost:8000/api/vehicles/1 \
  -H "Authorization: Bearer {ADMIN_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "rental_price_per_day": 55,
    "status": "available"
  }'
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Vehicle updated successfully",
  "data": {...}
}
```

### 2.5 Delete Vehicle (Admin Only)

```bash
curl -X DELETE http://localhost:8000/api/vehicles/1 \
  -H "Authorization: Bearer {ADMIN_TOKEN}"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Vehicle deleted successfully"
}
```

### 2.6 Authorization Test

```bash
# Customer should NOT be able to create vehicle (expect 403)
curl -X POST http://localhost:8000/api/vehicles \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"brand":"Test"}'
```

**Expected Response (403):**
```json
{
  "success": false,
  "message": "Unauthorized."
}
```

---

## 3. Booking Module Testing

### 3.1 Create Booking (Customer)

```bash
curl -X POST http://localhost:8000/api/bookings \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_id": 1,
    "pickup_location": "Airport Terminal",
    "return_location": "Airport Terminal",
    "pickup_date": "2026-08-01 10:00:00",
    "return_date": "2026-08-05 10:00:00",
    "notes": "Need airport pickup"
  }'
```

**Expected Response (201):**
```json
{
  "success": true,
  "message": "Booking created successfully",
  "data": {
    "id": 1,
    "booking_reference": "BK-20260723-ABC123",
    "status": "pending",
    "total_price": 200.00,
    ...
  }
}
```

### 3.2 View Customer Bookings (Customer)

```bash
curl -X GET http://localhost:8000/api/bookings \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Bookings retrieved successfully",
  "data": [...],
  "meta": {...}
}
```

### 3.3 View Booking Details (Customer/Admin)

```bash
curl -X GET http://localhost:8000/api/bookings/1 \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}"
```

### 3.4 Confirm Booking (Admin/Staff)

```bash
curl -X PUT http://localhost:8000/api/admin/bookings/1/confirm \
  -H "Authorization: Bearer {ADMIN_TOKEN}"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Booking confirmed successfully",
  "data": {
    "status": "confirmed",
    "payment_status": "pending",
    ...
  }
}
```

### 3.5 Reject Booking (Admin/Staff)

```bash
curl -X PUT http://localhost:8000/api/admin/bookings/2/reject \
  -H "Authorization: Bearer {ADMIN_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"reason": "Vehicle unavailable"}'
```

### 3.6 Pickup Booking (Admin/Staff)

```bash
curl -X PUT http://localhost:8000/api/admin/bookings/1/pickup \
  -H "Authorization: Bearer {ADMIN_TOKEN}"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Vehicle picked up successfully",
  "data": {
    "status": "active",
    ...
  }
}
```

### 3.7 Return Vehicle (Admin/Staff)

```bash
curl -X PUT http://localhost:8000/api/admin/bookings/1/return \
  -H "Authorization: Bearer {ADMIN_TOKEN}"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Vehicle returned successfully",
  "data": {
    "status": "completed",
    "payment_status": "paid",
    ...
  }
}
```

### 3.8 Cancel Booking (Customer)

```bash
curl -X PUT http://localhost:8000/api/bookings/3/cancel \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Booking cancelled successfully",
  "data": {
    "status": "cancelled",
    ...
  }
}
```

### 3.9 Admin View All Bookings (Admin/Staff)

```bash
curl -X GET http://localhost:8000/api/admin/bookings \
  -H "Authorization: Bearer {ADMIN_TOKEN}"
```

---

## 4. Payment Module Testing

### 4.1 Create Payment (Customer)

```bash
curl -X POST http://localhost:8000/api/payments \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": 1,
    "payment_method": "card",
    "transaction_reference": "TXN-20260723-ABC123"
  }'
```

**Expected Response (201):**
```json
{
  "success": true,
  "message": "Payment processed successfully",
  "data": {
    "id": 1,
    "amount": 200.00,
    "status": "paid",
    "paid_at": "2026-07-23T10:00:00.000000Z",
    ...
  }
}
```

### 4.2 View Payment History (Customer)

```bash
curl -X GET http://localhost:8000/api/payments \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Payments retrieved successfully",
  "data": [...],
  "meta": {...}
}
```

### 4.3 View Payment Details

```bash
curl -X GET http://localhost:8000/api/payments/1 \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}"
```

### 4.4 Mark Payment as Failed (Admin/Staff)

```bash
curl -X PUT http://localhost:8000/api/admin/payments/2/fail \
  -H "Authorization: Bearer {ADMIN_TOKEN}"
```

### 4.5 Refund Payment (Admin/Staff)

```bash
curl -X PUT http://localhost:8000/api/admin/payments/1/refund \
  -H "Authorization: Bearer {ADMIN_TOKEN}"
```

---

## 5. Review Module Testing

### 5.1 Add Review (Customer - after completed booking)

```bash
curl -X POST http://localhost:8000/api/vehicles/1/reviews \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": 1,
    "rating": 5,
    "comment": "Excellent car, very clean and well maintained!"
  }'
```

**Expected Response (201):**
```json
{
  "success": true,
  "message": "Review created successfully",
  "data": {
    "id": 1,
    "rating": 5,
    "comment": "Excellent car, very clean and well maintained!",
    ...
  }
}
```

### 5.2 View Reviews for Vehicle (Public)

```bash
curl -X GET http://localhost:8000/api/vehicles/1/reviews
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Reviews retrieved successfully",
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1,
    "average_rating": 5.0
  }
}
```

### 5.3 Delete Review (Customer own review / Admin)

```bash
curl -X DELETE http://localhost:8000/api/reviews/1 \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}"
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Review deleted successfully"
}
```

### 5.4 Validation Tests

```bash
# Test invalid rating (should fail - 422)
curl -X POST http://localhost:8000/api/vehicles/1/reviews \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"booking_id": 1, "rating": 6, "comment": "Test"}'

# Test duplicate review (should fail - 422)
curl -X POST http://localhost:8000/api/vehicles/1/reviews \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"booking_id": 1, "rating": 5, "comment": "Test"}'
```

---

## 6. Database Verification

### Check Tables Exist

```sql
-- PostgreSQL
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'public' 
ORDER BY table_name;
```

**Expected Tables:**
- bookings
- categories
- failed_jobs
- jobs
- job_batches
- migrations
- notifications
- password_reset_tokens
- payments
- personal_access_tokens
- reviews
- users
- vehicles
- vehicle_images

### Check Relationships

```sql
-- Check foreign keys on bookings table
SELECT 
    tc.table_name, 
    kcu.column_name, 
    ccu.table_name AS foreign_table_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY' 
    AND tc.table_name = 'bookings';
```

### Check Data Integrity

```sql
-- Verify booking has valid user and vehicle
SELECT b.id, b.booking_reference, b.status, u.name, v.brand, v.model
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN vehicles v ON b.vehicle_id = v.id;

-- Verify payment linked to booking
SELECT p.id, p.amount, p.status, b.booking_reference
FROM payments p
JOIN bookings b ON p.booking_id = b.id;

-- Verify review linked to booking and vehicle
SELECT r.id, r.rating, r.comment, b.booking_reference, v.brand, v.model
FROM reviews r
JOIN bookings b ON r.booking_id = b.id
JOIN vehicles v ON r.vehicle_id = v.id;
```

---

## 7. API Response Format Verification

All API responses should follow this format:

### Success Response
```json
{
  "success": true,
  "message": "Human-readable message",
  "data": {...}
}
```

### Success with Pagination
```json
{
  "success": true,
  "message": "Human-readable message",
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description"
}
```

### Validation Error Response (422)
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Error message"]
  }
}
```

---

## 8. HTTP Status Codes Verification

| Operation | Expected Code |
|-----------|---------------|
| Create resource | 201 |
| View resource | 200 |
| Update resource | 200 |
| Delete resource | 200 |
| Validation error | 422 |
| Unauthorized | 403 |
| Not found | 404 |
| Server error | 500 |

---

## 9. Relationship Verification Checklist

### User → Bookings
- [ ] Customer can see only their bookings
- [ ] Admin/Staff can see all bookings
- [ ] Booking belongs to correct user

### Vehicle → Bookings
- [ ] Vehicle has many bookings
- [ ] Booking belongs to correct vehicle
- [ ] Vehicle status updates on booking status change

### Booking → Payment
- [ ] Booking has many payments
- [ ] Payment belongs to correct booking
- [ ] Payment updates booking payment_status

### User → Reviews
- [ ] User has many reviews
- [ ] Review belongs to correct user

### Vehicle → Reviews
- [ ] Vehicle has many reviews
- [ ] Review belongs to correct vehicle
- [ ] Vehicle average rating calculated correctly

---

## 10. Authorization Verification Checklist

### Vehicle CRUD
- [ ] Public can view vehicles
- [ ] Admin can create/update/delete vehicles
- [ ] Fleet Manager can create/update vehicles
- [ ] Staff can only view vehicles
- [ ] Customer can only view vehicles

### Booking CRUD
- [ ] Customer can create bookings
- [ ] Customer can view own bookings
- [ ] Customer can cancel own pending/confirmed bookings
- [ ] Admin/Staff can confirm/reject/pickup/return bookings
- [ ] Admin/Staff can view all bookings

### Payment CRUD
- [ ] Customer can create payments for own bookings
- [ ] Customer can view own payments
- [ ] Admin/Staff can mark as failed/refund

### Review CRUD
- [ ] Customer can create reviews for own completed bookings
- [ ] Customer can delete own reviews
- [ ] Admin can delete any reviews
- [ ] Public can view approved reviews

---

## 11. Notification Verification

After creating a booking, payment, or review, check the `notifications` table:

```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
```

**Expected notifications:**
- BookingCreated
- BookingConfirmed
- BookingCancelled
- BookingRejected
- BookingPickedUp
- BookingCompleted
- PaymentSucceeded
- PaymentFailed
- ReviewSubmitted

---

## 12. Final Checklist

### Database
- [ ] All migrations run without errors
- [ ] All tables created with correct structure
- [ ] Foreign keys properly defined
- [ ] Indexes created for performance

### Models
- [ ] All models have correct fillable fields
- [ ] All relationships defined correctly
- [ ] All casts defined correctly
- [ ] All scopes defined correctly

### Services
- [ ] BookingService handles all booking operations
- [ ] PaymentService handles all payment operations
- [ ] ReviewService handles all review operations
- [ ] Business logic in services, not controllers

### Controllers
- [ ] All controllers are thin
- [ ] All controllers use form requests for validation
- [ ] All controllers use API resources for responses
- [ ] All controllers handle exceptions properly

### Policies
- [ ] All policies registered in AppServiceProvider
- [ ] All authorization checks in policies
- [ ] Role-based access control working

### Notifications
- [ ] All notification classes created
- [ ] All events created
- [ ] All listeners created
- [ ] EventServiceProvider registered
- [ ] Notifications sent via events, not directly

### Routes
- [ ] All routes defined in api.php
- [ ] Public routes accessible without auth
- [ ] Protected routes require auth
- [ ] Admin routes require role middleware

---

## 13. Commit Instructions

Once all tests pass:

```bash
# Stage all changes
git add .

# Commit with descriptive message
git commit -m "feat: complete Member 2 implementation - Bookings, Payments, Reviews

- Implemented Vehicle CRUD APIs
- Implemented Booking CRUD with status management
- Implemented Payment processing
- Implemented Review system
- Added Event-driven notification system
- Added Policies for authorization
- Added Form Requests for validation
- Added API Resources for response formatting
- All tests passing"

# Push to feature branch
git push origin feature/bookings-payments-reviews
```

---

## 14. Troubleshooting

### Common Issues

1. **401 Unauthorized**: Ensure Bearer token is included in Authorization header
2. **403 Forbidden**: Check user role has required permissions
3. **422 Validation Error**: Check request body matches required fields
4. **500 Server Error**: Check Laravel logs in `storage/logs/laravel.log`

### Check Logs

```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# Check queue worker (if using queued notifications)
php artisan queue:work
```

---

## Summary

Member 2 is responsible for:

| Module | Status |
|--------|--------|
| Vehicle CRUD | ✅ Implemented |
| Booking CRUD | ✅ Implemented |
| Payment Processing | ✅ Implemented |
| Review System | ✅ Implemented |
| Database Models | ✅ Implemented |
| Relationships | ✅ Implemented |
| Authorization | ✅ Implemented |
| Notifications | ✅ Implemented |

All tasks are complete and ready for testing!
