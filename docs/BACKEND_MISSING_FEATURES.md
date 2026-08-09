# Backend Missing Features

This document outlines features and endpoints that the DriveEase Flutter application expects or has UI elements for, but which are currently unimplemented or missing on the Laravel backend. 

*Note: The Flutter application handles all these missing features gracefully (using "Not implemented yet" SnackBars, disabled buttons, or warning banners).*

## 1. Authentication & Profile
- **Forgot Password / Password Reset**: The `/api/password/forgot` and `/api/password/reset` endpoints do not exist.
- **Change Password**: The authenticated user cannot change their password via the API (e.g., `/api/user/password`).
- **Social Login**: OAuth integration (Google, Apple) is present in the UI but there is no corresponding backend flow (e.g., `/api/auth/social`).

## 2. Payments & Transactions
- **Payment Methods Management**: The UI has screens to add and list credit cards (`/api/payment-methods`), but the backend does not support saving or managing payment methods.
- **Invoice Downloads**: Downloading a PDF receipt or invoice (`/api/transactions/{id}/download`) is mocked in the app.

## 3. Support & Contact
- **Contact Us / Support Tickets**: The app has a `SupportScreen` that attempts to send contact inquiries via `/api/contact` or `/api/support/tickets`. This is currently mocked using an `EmptyStateWidget` and delayed dummy responses because the endpoint is missing.

## 4. Vehicle & Reservation Integrations
- **Vehicle External Maps**: Opening a vehicle's or branch's location in external maps (Google Maps/Apple Maps) via deep links is stubbed.
- **Vehicle Inspection Camera**: Adding damage photos or scanning QR codes during pickup/return inspections is mocked; the backend does not yet handle inspection image uploads beyond the basic vehicle creation.
- **Review Deletion**: There is no endpoint to delete an existing review (e.g., `DELETE /api/reviews/{id}`).

## 5. User Avatar Uploads
- **Profile Image Persistence**: While the `/api/user/profile` endpoint exists, handling multi-part form data for `avatar` or `profile_image` seems unreliable or missing in the backend controller. The app successfully picks the image and sends the multipart request, but the backend doesn't always reflect the changed URL.

---
**Recommendation for Backend Team:**
Prioritize adding the `change_password` and `forgot_password` routes, as well as the `contact_us` endpoints, since they are standard features for a complete mobile experience.
