# Complete Prompt: Build a Production-Ready Car Rental System Frontend

You are a senior frontend architect and React developer.

Build a complete, modern, production-ready car rental system frontend for a professional car rental company.

The backend is a separate Laravel REST API. The frontend must communicate with the backend only through HTTP API requests.

The frontend must not contain business logic that belongs to the backend.

The frontend must be designed to work with the following backend concepts:

* Authentication
* Customers
* Admins
* Staff
* Fleet Managers
* Vehicles
* Vehicle Categories
* Bookings
* Payments
* Reviews
* Maintenance
* Notifications
* Contact Messages
* Dashboard Statistics

The frontend must be built independently from the backend visual implementation. Use the API contract provided by the backend.

---

# 1. Technology Stack

Use:

* React
* TypeScript
* Vite
* Tailwind CSS
* React Router
* Axios
* Zustand for global state management
* React Hook Form
* Zod for validation where appropriate
* Lucide React for icons
* Recharts for dashboard charts
* Framer Motion for animations
* ESLint
* Prettier

Use modern React best practices.

Use functional components and React hooks.

Do not use unnecessary class components.

---

# 2. Project Goal

Create a complete car rental website with:

## Public Website

Visitors can:

* View the homepage
* Browse available vehicles
* Search vehicles
* Filter vehicles
* Sort vehicles
* View vehicle details
* View vehicle images
* View ratings and reviews
* View rental prices
* Contact the company
* Register
* Login

## Customer Dashboard

Customers can:

* View their profile
* Update their profile
* View their bookings
* Create a booking
* View booking details
* Cancel eligible bookings
* View payment status
* Leave reviews for completed rentals
* View notifications

## Admin Dashboard

Admins can:

* View system statistics
* Manage users
* Manage vehicles
* Manage categories
* Manage bookings
* Manage payments
* Manage reviews
* Manage maintenance
* View revenue statistics
* View visitor analytics
* Manage contact messages

## Fleet Manager Dashboard

Fleet managers can:

* View vehicles
* Add vehicles
* Edit vehicles
* Update vehicle status
* Manage vehicle maintenance
* View vehicle history

## Staff Dashboard

Staff members can:

* View bookings
* Confirm vehicle pickup
* Confirm vehicle return
* Update rental status

---

# 3. Design Direction

Create a premium, modern car rental experience.

The website should feel:

* Professional
* Premium
* Trustworthy
* Modern
* Fast
* Clean
* Easy to navigate

Use a strong visual hierarchy.

Use high-quality vehicle presentation.

Use:

* Large vehicle images
* Spacious layouts
* Clear typography
* Modern cards
* Smooth hover effects
* Subtle animations
* Responsive layouts
* Professional dashboards

Do not make the design overly complicated.

The interface must prioritize usability.

---

# 4. Responsive Design

The website must work properly on:

* Desktop
* Laptop
* Tablet
* Mobile phone

Use responsive Tailwind CSS classes.

Test important pages at:

```text
320px
375px
768px
1024px
1280px
1440px
```

The navigation must work properly on mobile.

Use a mobile menu.

Tables should become responsive on smaller screens.

Dashboard layouts should adapt to mobile.

---

# 5. Public Pages

Create the following routes.

## Home

```text
/
```

The homepage should include:

### Hero Section

Include:

* Strong headline
* Supporting text
* Search or vehicle discovery interface
* Primary call-to-action
* Premium vehicle visual

Example content:

```text
Drive Your Journey
With Confidence
```

The exact text may be improved by the AI.

### Featured Vehicles

Display featured vehicles from the API.

Each vehicle card should show:

* Image
* Brand
* Model
* Category
* Price per day
* Transmission
* Fuel type
* Seats
* Availability
* View details button

### How It Works

Show:

```text
1. Choose your vehicle
2. Select your rental dates
3. Confirm your booking
4. Enjoy your journey
```

### Why Choose Us

Show:

* Wide vehicle selection
* Easy booking
* Professional support
* Secure rental process

### Customer Reviews

Display reviews from the backend API.

### Call to Action

Encourage users to browse vehicles.

---

# 6. Vehicle Browsing Page

Route:

```text
/vehicles
```

Display all vehicles from:

```text
GET /api/vehicles
```

Create:

* Search input
* Category filter
* Price range filter
* Fuel type filter
* Transmission filter
* Seats filter
* Availability filter
* Featured filter
* Sort dropdown
* Pagination

Use URL query parameters where appropriate.

Example:

```text
/vehicles?search=Toyota&category=SUV&sort=price_asc
```

The page must display:

* Loading state
* Error state
* Empty state
* Vehicle results
* Pagination

---

# 7. Vehicle Details Page

Route:

```text
/vehicles/:id
```

Display:

* Vehicle image gallery
* Brand
* Model
* Year
* Category
* Description
* Fuel type
* Transmission
* Number of seats
* Color
* Mileage
* Rental price per day
* Availability status
* Average rating
* Reviews

Include a clear:

```text
Book This Vehicle
```

button.

If the user is not logged in, redirect them to login before booking.

---

# 8. Authentication

Create:

```text
/login
/register
/forgot-password
/reset-password
```

## Login

Fields:

* Email
* Password

Features:

* Validation
* Show/hide password
* Loading state
* Error messages
* Successful login redirect

## Registration

Fields:

* Name
* Email
* Phone
* Password
* Password confirmation

Features:

* Client-side validation
* Server-side error handling
* Password strength feedback

Store authentication state securely.

Do not store sensitive data unnecessarily.

Use Axios interceptors for authentication handling.

---

# 9. Authentication State

Create a global authentication store using Zustand.

Example state:

```typescript
type AuthState = {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
};
```

The application should:

1. Check whether the user is authenticated.
2. Load the current user.
3. Protect private routes.
4. Redirect unauthorized users.
5. Redirect users based on their roles.

---

# 10. Role-Based Routing

Create route protection.

## Customer Routes

```text
/dashboard
/dashboard/profile
/dashboard/bookings
/dashboard/bookings/:id
/dashboard/reviews
/dashboard/notifications
```

## Admin Routes

```text
/admin
/admin/users
/admin/vehicles
/admin/categories
/admin/bookings
/admin/payments
/admin/reviews
/admin/maintenance
/admin/messages
/admin/analytics
```

## Fleet Manager Routes

```text
/fleet
/fleet/vehicles
/fleet/vehicles/create
/fleet/vehicles/:id/edit
/fleet/maintenance
```

## Staff Routes

```text
/staff
/staff/bookings
/staff/bookings/:id
```

Users must not be able to access pages that their role is not authorized to access.

---

# 11. Customer Dashboard

Create a professional customer dashboard.

Route:

```text
/dashboard
```

Display:

* Total bookings
* Active bookings
* Completed bookings
* Pending bookings
* Recent bookings

Include:

* Quick actions
* Booking history
* Notifications
* Profile summary

---

# 12. Booking Flow

Create a complete booking experience.

When a user selects a vehicle:

1. Show vehicle information.
2. Select pickup date.
3. Select return date.
4. Select pickup location.
5. Select return location.
6. Calculate rental duration.
7. Display estimated price.
8. Display additional charges if applicable.
9. Display total price.
10. Confirm booking.

The frontend may display a price estimate, but the backend must calculate and verify the final price.

Never trust the frontend price.

Example:

```text
Pickup Date: 2026-08-01
Return Date: 2026-08-05
Rental Days: 4
Price Per Day: $100
Estimated Total: $400
```

Create a confirmation page.

After successful booking:

```text
Booking Created Successfully
Booking Reference: CR-2026-0001
```

---

# 13. Customer Booking Management

Create:

```text
/dashboard/bookings
```

Display:

* Booking reference
* Vehicle
* Pickup date
* Return date
* Total price
* Booking status
* Payment status

Actions:

* View details
* Cancel booking when allowed
* View payment information
* Leave review after completion

Use status badges.

Example statuses:

```text
Pending
Confirmed
Active
Completed
Cancelled
Rejected
```

---

# 14. Admin Dashboard

Create a professional admin dashboard.

Route:

```text
/admin
```

Display:

* Total users
* Total vehicles
* Available vehicles
* Rented vehicles
* Vehicles under maintenance
* Total bookings
* Active rentals
* Total revenue

Add charts using Recharts:

* Monthly revenue
* Booking trends
* Vehicle popularity
* Booking status distribution

Display:

* Recent bookings
* Recent users
* Popular vehicles

The dashboard must consume data from:

```text
GET /api/admin/dashboard
```

---

# 15. Admin Vehicle Management

Route:

```text
/admin/vehicles
```

Features:

* List vehicles
* Search vehicles
* Filter vehicles
* Create vehicle
* Edit vehicle
* Delete vehicle
* Update vehicle status
* Mark vehicle as featured

Create forms for:

```text
Brand
Model
Year
Category
Registration Number
VIN
Description
Fuel Type
Transmission
Seats
Color
Mileage
Purchase Price
Rental Price Per Day
Status
Location
Images
```

Use reusable form components.

---

# 16. Vehicle Image Management

Support:

* Multiple images
* Image preview
* Primary image selection
* Remove image
* Upload image

Display images in a professional gallery.

The frontend must communicate with the backend image API according to the backend API contract.

---

# 17. Category Management

Admin can:

* Create category
* Edit category
* Delete category
* View categories

Use:

```text
/admin/categories
```

---

# 18. User Management

Admin can:

* View users
* Search users
* Filter users by role
* View user details
* Update user role
* Disable users where supported

Display:

* Name
* Email
* Phone
* Role
* Account status
* Registration date

---

# 19. Booking Management

Admin and staff can:

* View all bookings
* Search bookings
* Filter by status
* View booking details
* Confirm bookings
* Reject bookings
* Confirm pickup
* Confirm return

Use appropriate confirmation dialogs for destructive actions.

---

# 20. Payment Interface

Create payment-related interfaces.

Display:

* Payment amount
* Payment status
* Payment method
* Transaction reference
* Payment date

Do not display or request:

* Full card numbers
* CVV
* Sensitive payment credentials

The frontend must only communicate with the backend payment API.

---

# 21. Review System

Customers can review completed rentals.

Create:

```text
/dashboard/reviews
```

A review should include:

* Rating from 1 to 5
* Comment

Display vehicle reviews on the vehicle details page.

Use star rating components.

---

# 22. Maintenance Management

Fleet managers and admins can:

* View maintenance records
* Create maintenance records
* Update maintenance records
* Mark maintenance as completed
* View vehicle maintenance history

Display:

* Vehicle
* Maintenance type
* Start date
* End date
* Cost
* Status
* Notes

---

# 23. Contact Page

Create:

```text
/contact
```

Form fields:

* Name
* Email
* Phone
* Subject
* Message

Display:

* Success state
* Validation errors
* Loading state
* Error state

Send data to:

```text
POST /api/contact
```

---

# 24. API Architecture

Create a clean API layer.

Example:

```text
src/
├── api/
│   ├── axios.ts
│   ├── authApi.ts
│   ├── vehicleApi.ts
│   ├── bookingApi.ts
│   ├── userApi.ts
│   ├── paymentApi.ts
│   ├── reviewApi.ts
│   └── adminApi.ts
```

Do not put Axios requests directly inside every component.

Use API service modules.

---

# 25. Recommended Folder Structure

Use this structure:

```text
src/
│
├── assets/
│
├── components/
│   ├── common/
│   ├── ui/
│   ├── layout/
│   ├── vehicles/
│   ├── bookings/
│   ├── reviews/
│   └── dashboard/
│
├── pages/
│   ├── public/
│   ├── auth/
│   ├── customer/
│   ├── admin/
│   ├── fleet/
│   └── staff/
│
├── layouts/
│   ├── PublicLayout.tsx
│   ├── DashboardLayout.tsx
│   └── AdminLayout.tsx
│
├── routes/
│   ├── AppRoutes.tsx
│   ├── ProtectedRoute.tsx
│   └── RoleRoute.tsx
│
├── api/
│
├── store/
│   ├── authStore.ts
│   ├── vehicleStore.ts
│   └── bookingStore.ts
│
├── hooks/
│
├── types/
│
├── utils/
│
├── lib/
│
├── config/
│
├── App.tsx
└── main.tsx
```

---

# 26. TypeScript Types

Create reusable types.

Example:

```typescript
export interface Vehicle {
  id: number;
  brand: string;
  model: string;
  year: number;
  category: Category;
  description: string;
  fuel_type: string;
  transmission: string;
  seats: number;
  color: string;
  mileage: number;
  rental_price_per_day: number;
  status: VehicleStatus;
  featured: boolean;
  images: VehicleImage[];
}
```

Create types for:

```text
User
Vehicle
Category
Booking
Payment
Review
Maintenance
Notification
DashboardStatistics
ApiResponse
Pagination
```

Do not use `any` unless absolutely necessary.

---

# 27. Loading, Error, and Empty States

Every API-based page must handle:

## Loading

Show:

* Skeleton loaders
* Loading indicators

## Error

Show:

* Clear error message
* Retry button where appropriate

## Empty

Examples:

```text
No vehicles found.
No bookings yet.
No reviews available.
No users found.
```

Do not leave blank screens.

---

# 28. Notifications and Toasts

Create a consistent notification system.

Display success messages for:

* Successful login
* Successful registration
* Successful booking
* Successful update
* Successful deletion

Display errors for:

* Failed requests
* Validation errors
* Unauthorized actions

Do not show technical stack traces to users.

---

# 29. Accessibility

Follow accessibility best practices.

Use:

* Semantic HTML
* Labels for form inputs
* Keyboard navigation
* Visible focus states
* Accessible buttons
* Accessible dialogs
* Appropriate ARIA attributes

Images must have useful alt text.

---

# 30. Performance

Optimize the application.

Use:

* Lazy-loaded routes
* Code splitting
* Optimized images
* Pagination
* Debounced search
* Memoization where appropriate
* Avoid unnecessary API requests

Do not load all database records at once.

---

# 31. Animation

Use Framer Motion carefully.

Add subtle animations for:

* Page transitions
* Vehicle cards
* Modals
* Dropdowns
* Dashboard cards
* Navigation

Animations must not make the application slow or difficult to use.

Respect reduced-motion preferences where possible.

---

# 32. Environment Variables

Create:

```text
.env.example
```

Example:

```env
VITE_API_URL=http://localhost:8000/api
```

Use:

```typescript
const API_URL = import.meta.env.VITE_API_URL;
```

Never hardcode production API URLs throughout the codebase.

Do not commit `.env` files containing secrets.

---

# 33. API Error Handling

Create centralized Axios error handling.

Handle:

```text
401 Unauthorized
403 Forbidden
404 Not Found
422 Validation Error
500 Server Error
Network Error
```

For a 401 response:

* Clear invalid authentication state
* Redirect to login when appropriate

For a 422 response:

* Display validation errors beside the relevant fields

---

# 34. API Contract

Assume the backend provides routes such as:

```text
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/vehicles
GET    /api/vehicles/{id}
POST   /api/vehicles
PUT    /api/vehicles/{id}
DELETE /api/vehicles/{id}

GET    /api/categories
POST   /api/categories
PUT    /api/categories/{id}
DELETE /api/categories/{id}

POST   /api/bookings
GET    /api/bookings
GET    /api/bookings/{id}
PUT    /api/bookings/{id}/cancel

GET    /api/vehicles/{vehicle}/reviews
POST   /api/vehicles/{vehicle}/reviews

GET    /api/admin/dashboard
```

Before implementing API calls, inspect the actual backend API documentation.

If the backend route differs from this list, follow the actual backend API contract.

Do not invent API endpoints unnecessarily.

---

# 35. Development Rules

Follow these rules:

1. Inspect the existing project before modifying it.
2. Do not delete working code unnecessarily.
3. Do not create duplicate components.
4. Do not duplicate API logic.
5. Use reusable components.
6. Keep components focused.
7. Use TypeScript strictly.
8. Avoid `any`.
9. Keep API requests outside presentation components where possible.
10. Use environment variables.
11. Handle all loading states.
12. Handle all error states.
13. Handle empty states.
14. Use responsive design.
15. Protect private routes.
16. Enforce role-based access.
17. Never trust frontend data for security.
18. The backend remains the source of truth.
19. Use the backend API contract.
20. Do not hardcode fake production data when API data is available.

---

# 36. Development Order

Build the frontend in this order.

## Phase 1

Analyze the project and create the frontend architecture.

## Phase 2

Set up React, TypeScript, Vite, Tailwind CSS, and required dependencies.

## Phase 3

Create the application layout and routing system.

## Phase 4

Create the API client and environment configuration.

## Phase 5

Create authentication and authentication state.

## Phase 6

Create public layout and homepage.

## Phase 7

Create vehicle browsing and filtering.

## Phase 8

Create vehicle details page.

## Phase 9

Create booking flow.

## Phase 10

Create customer dashboard.

## Phase 11

Create admin dashboard.

## Phase 12

Create vehicle management.

## Phase 13

Create user management.

## Phase 14

Create booking management.

## Phase 15

Create payment interfaces.

## Phase 16

Create reviews.

## Phase 17

Create maintenance management.

## Phase 18

Create contact system.

## Phase 19

Add loading, error, and empty states.

## Phase 20

Add responsive improvements.

## Phase 21

Add accessibility improvements.

## Phase 22

Add performance optimizations.

## Phase 23

Test the complete frontend with the backend API.

## Phase 24

Prepare the frontend for deployment.

---

# 37. Final Requirement

Do not generate random frontend code.

Build a real professional application.

Before implementing each major feature:

1. Explain the purpose of the feature.
2. Explain the components required.
3. Explain the API endpoints required.
4. Implement the feature.
5. Test the feature.
6. Check for TypeScript errors.
7. Check for ESLint errors.
8. Check responsive behavior.
9. Explain how the feature communicates with the Laravel backend.

The final result must be a complete, modern, responsive, accessible, maintainable React frontend for the professional car rental system.

The frontend must be designed to work with the Laravel REST API and PostgreSQL-backed backend described in the backend architecture.

Build the project step by step and do not move to the next major phase until the current phase is working correctly.
