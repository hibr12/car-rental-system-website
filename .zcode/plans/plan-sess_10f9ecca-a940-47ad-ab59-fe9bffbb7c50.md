# DriveEase ↔ Laravel Integration Plan (Refined)

## Non-negotiable constraints honored
- **Laravel backend is strictly READ-ONLY** — inspected only, never modified.
- **Only the Flutter app** (`C:\Users\hp\Desktop\mobile`) + new `docs/BACKEND_MISSING_FEATURES.md` change.
- **No faking missing endpoints, no removing features/screens.** Unsupported screens get clean loading/empty/"unavailable"/"coming soon" states + repository `TODO` + documentation.
- **No model/endpoint guessing** — every model field & endpoint verified against backend Resources/Controllers (audit complete).
- **Reuse existing widgets** (`EmptyStateWidget`, `ErrorStateWidget`, `StatusBadge`, `AppTextField`, `PrimaryButton`, `VehicleCard`, `PaginatedListBuilder`, `CachedNetworkImage`, `FilterBottomSheet`) — no duplicate code, no full redesign.
- Keep existing architecture (singletons, `package:http`, per-screen StatefulWidget fetching) — refactor only where integration demands.

---

## Phase 1 — Centralized config + networking foundation
1. **`android/app/src/main/AndroidManifest.xml`**: add `android:usesCleartextTraffic="true"` (required for HTTP to `192.168.1.8`; otherwise Android blocks every call).
2. **`lib/core/config/app_config.dart`**: keep `http://192.168.1.8:8000/api`; fix stale comment; add `timeoutDuration`.
3. **`lib/core/config/api_endpoints.dart`** (new): **single source of truth** for every path (`auth.login = '/auth/login'`, `vehicles = '/vehicles'`, `bookings`, `payments`, `reviews`, `categories`, `contactMessages`, etc.) — satisfies "centralize all endpoints, no hardcoded URLs" (#9). Repositories import from here.
4. **`lib/data/api/api_client.dart`** (production upgrade, still `package:http`):
   - Connect/read **timeouts** → `ApiError(isTimeout)`.
   - **Network/offline** detection (`SocketException`/`HandshakeException`/`ClientException`) → `ApiError(isNetworkError)`.
   - **401**: invoke settable `onUnauthorized` (registered in `main.dart`) → clear token + `AuthState`, route to `/login`.
   - Map **403/404/429/500** with friendly messages; keep Laravel **422** parsing; tolerate non-JSON 5xx bodies.
   - Add **`multipart()`** for avatar-upload readiness (used if/when backend adds it; otherwise TODO).
5. **`lib/data/models/api_models.dart`**: add `isNetworkError`/`isTimeout` flags; helper to flatten validation errors.

## Phase 2 — Auth state, bootstrap, routing guard
6. **`lib/core/config/auth_state.dart`** (new): tiny sync holder (`token`, `isAuthenticated`) for the router redirect.
7. **`lib/main.dart`**: `_bootstrap()` loads token into `AuthState` before `runApp`; register `ApiClient.onUnauthorized`.
8. **`lib/core/routes/app_routes.dart`**: `redirect:` guard for protected routes; **remove duplicate `/booking-success`**; safe `state.extra` casting (no throws); accept `BookingDraft` (Phase 5) for summary/success.
9. **`lib/screens/splash/splash_screen.dart`**: auto-login → home if authenticated, else onboarding.

## Phase 3 — Model robustness (verified against backend Resources)
10. **`vehicle_model.dart`**: guarantee non-empty `imageUrls` (placeholder fallback) — fixes `.first` crashes in `VehicleCard`/reservations/details; keep `category`/`isAvailable`/`status`/`mileage`.
11. **`booking_model.dart`**: no-throw on missing vehicle (placeholder fallback so one record can't blank the list); safe date parsing; capture `paymentStatus`, `numberOfDays`, `pricePerDay`, `notes`.
12. **`user_model.dart`**: expose `role` (from `UserResource`) for any future gating.
13. **`owner_model.dart`** / **`review_model.dart`** / **`transaction_model.dart`**: safe `DateTime.parse`/null hardening.

## Phase 4 — Vehicles: search, filter, pagination, categories
14. **`vehicle_repository.dart`**: use centralized endpoints; extend query params (`search`, `category` as **slug**, `min_price`, `max_price`, `fuel_type`, `transmission`, `status`, `sort`, `per_page`, `page`).
15. **`browse_screen.dart`**: wire search text + real categories (slug FilterChips from `/categories`) + `FilterBottomSheet` result → query; add infinite-scroll pagination via `PaginatedListBuilder`; full loading/empty/network/error states.
16. **`filter_bottom_sheet.dart`**: return a typed filter-result map consumed by browse (instead of no-op pop).
17. **`home_screen.dart`**: "See All" → Browse; category chips → Browse filtered; real avatar.

## Phase 5 — Booking flow: real dates + pricing
18. **`lib/models/booking_draft.dart`** (new): `{vehicle, pickupDate, returnDate, pickupTime, returnTime, extras}` threaded through the flow.
19. **`booking_date_screen.dart`** / **`extras_screen.dart`**: forward chosen dates/extras via `BookingDraft`.
20. **`booking_summary_screen.dart`**: compute days + total from **real** dates; replace hardcoded "Aug 12–15"; `createBooking` sends correct dates (drop `totalAmount: 0`; server computes `total_price`).
21. **`booking_success_screen.dart`**: show real `booking_reference` (replace "BKG-847291").

## Phase 6 — Reservations, cancel, payments
22. **`reservations_screen.dart`**: pull-to-refresh + error state; categorize **all** statuses; safe images.
23. **`reservation_details_screen.dart`**: timeline from real dates; show `payment_status`; **"Pay Now"** for unpaid/confirmed → `PaymentRepository`.
24. **`cancel_reservation_screen.dart`**: wire "Yes, Cancel" → `BookingRepository.cancelBooking`; refresh + server-error surfacing.
25. **`payment_repository.dart`** (new): `POST /payments` (`booking_id`, `amount` = booking `totalAmount` per server rule, `payment_method`); document the `amount == total_price` rule. `GET /payments` stays in `transaction_repository`.
26. **`transaction_history_screen.dart`**: harden error/empty; document that `PaymentResource` has no nested `vehicle` (vehicleName may show "Vehicle").

## Phase 7 — Reviews & support
27. **`write_review_screen.dart`**: wire submit → `ReviewRepository.addReview`; handle 422 (already-reviewed/not-completed) + 403 messages.
28. **`reviews_screen.dart`**: "Write a Review" → informative guidance (needs a completed booking) — no fake call; use `meta.average_rating`.
29. **`support_screen.dart`**: add a real contact form → `POST /contact-messages` (public endpoint) via new `ContactRepository`.

## Phase 8 — Profile & auth screens
30. **`register_screen.dart`**: wire to real `register(...)` — add missing `phone` + `password confirmation`; show 422 errors; set `AuthState`.
31. **`login_screen.dart`**: keep real call; improve 401/422 messaging; set `AuthState`.
32. **`profile_screen.dart`**: real avatar + conditional verified badge.
33. **`edit_profile_screen.dart`**: send `email` too; show 422; real avatar.
34. **No-endpoint screens** (`change_password`, `forgot_password`, `driver_license`, `payment_methods`/`add_payment_method`, `saved_addresses`, `rewards`): **kept intact**, clean non-fake states, documented. Favorites & addresses stay local via `LocalStorageService` (document no sync endpoint).

## Phase 9 — Interaction/UX polish pass (#10, #13)
35. Fix all dead buttons / no-op SnackBars; ensure all navigation targets resolve.
36. Polish spacing/typography/cards/consistency only where broken or confusing (no redesign); fix overflows/responsiveness.
37. **`notifications_screen.dart`**: replace hardcoded mock with clean `EmptyStateWidget` ("unavailable" — no endpoint).

## Phase 10 — Quality gate (#14, #15)
38. `flutter pub get` → `dart format .` → `flutter analyze` (resolve all errors + actionable warnings, remove unused imports/dead code) → `flutter test` if tests exist; report results.

## Phase 11 — Documentation & final report (#5, #16)
39. **`docs/BACKEND_MISSING_FEATURES.md`**: missing endpoints (notifications, favorites sync, password reset/change, payment-method CRUD, rewards, branches, license/avatar uploads, invoice/`invoice_url`, booking-extend, vehicle features, public maintenance), missing fields (`PaymentResource` no nested vehicle; `UserResource` no reward points/license), validation gaps (`StoreBookingRequest` ignores `additional_charges`/`discount` the service reads), inconsistencies (per_page fixed-15 vs param-driven; custom `meta`; `vehicles.status` free string), recommendations.
40. Final integration report: integrated APIs, fully/partially functional screens, remaining backend dependencies, missing features, UI/UX improvements, known issues, production-readiness score /100, files modified.

---

## Key design decisions
- **Architecture preserved** (singletons + `http` + StatefulWidget fetching) — no new state libs; small, reviewable diff; keeps `flutter analyze` clean.
- **Single-source-of-truth endpoints** (`api_endpoints.dart`) satisfies the centralization rule.
- **Auto-login + 401 auto-logout** via `AuthState` + router `redirect` + `ApiClient.onUnauthorized` — no global state library needed.
- **Single-point image fallback** in `Vehicle.fromJson` prevents crashes everywhere.
- Every screen gets graceful loading/empty/network/timeout/401/422/5xx coverage using existing `EmptyStateWidget`/`ErrorStateWidget`.

Final deliverables: integrated APIs · fully functional screens · partially functional screens · remaining backend dependencies · missing backend features · UI/UX improvements · known issues · production-readiness score · files modified.