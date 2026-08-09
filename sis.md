All Your Endpoints (Member 2) - Full URLs
Auth (Public)
POST   http://localhost:8000/api/auth/register
POST   http://localhost:8000/api/auth/login
Bookings (Customer) - Requires Bearer Token
GET    http://localhost:8000/api/bookings
POST   http://localhost:8000/api/bookings
GET    http://localhost:8000/api/bookings/check-availability
GET    http://localhost:8000/api/bookings/price-estimate
GET    http://localhost:8000/api/bookings/{booking_id}
PUT    http://localhost:8000/api/bookings/{booking_id}/cancel
Bookings (Admin/Staff) - Requires Bearer Token + Admin Role
GET    http://localhost:8000/api/admin/bookings
PUT    http://localhost:8000/api/admin/bookings/{booking_id}/confirm
PUT    http://localhost:8000/api/admin/bookings/{booking_id}/reject
PUT    http://localhost:8000/api/admin/bookings/{booking_id}/pickup
PUT    http://localhost:8000/api/admin/bookings/{booking_id}/return
Payments (Customer) - Requires Bearer Token
GET    http://localhost:8000/api/payments
POST   http://localhost:8000/api/payments
POST   http://localhost:8000/api/payments/initialize
GET    http://localhost:8000/api/payments/verify/{tx_ref}
GET    http://localhost:8000/api/payments/{payment_id}
Payments (Public Callback - No Auth)
POST   http://localhost:8000/api/payments/callback
Payments (Admin) - Requires Bearer Token + Admin Role
PUT    http://localhost:8000/api/admin/payments/{payment_id}/fail
PUT    http://localhost:8000/api/admin/payments/{payment_id}/refund
Reviews - Requires Bearer Token
GET    http://localhost:8000/api/vehicles/{vehicle_id}/reviews
GET    http://localhost:8000/api/reviews
POST   http://localhost:8000/api/vehicles/{vehicle_id}/reviews
PUT    http://localhost:8000/api/reviews/{review_id}
DELETE http://localhost:8000/api/reviews/{review_id}
Notifications - Requires Bearer Token
GET    http://localhost:8000/api/notifications
PUT    http://localhost:8000/api/notifications/read-all
GET    http://localhost:8000/api/notifications/{notification_id}
PUT    http://localhost:8000/api/notifications/{notification_id}/read
DELETE http://localhost:8000/api/notifications/{notification_id}
Replace {booking_id}, {payment_id}, {vehicle_id}, {review_id}, {notification_id}, {tx_ref} with actual IDs.
Headers for all authenticated requests:
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
▣  Build · Nemotron 3 Ultra Free · 29.7s
how to start backend
+ Thought: 4.1s
→Read backend\composer.json 
unknown certificate verification error
continue
unknown certificate verification error
▣  Build · Nemotron 3 Ultra Free