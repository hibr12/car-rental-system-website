# Apex Rentals — Mobile App

A production-ready car rental mobile application built with Flutter. It connects to the Apex Rentals Laravel API (Sanctum bearer-token auth) to provide the full customer journey: browse vehicles, book, pay (Chapa or cash-at-branch), manage reservations, upload a driver's license, and review rentals.

## Features

- **Authentication**: Login, registration, and session validation (`GET /auth/me`) with the Sanctum token stored in `flutter_secure_storage` (encrypted on-device).
- **Vehicle Browsing**: Debounced search, category filters, price/fuel/transmission/seat filter sheet, infinite-scroll pagination, grid/list toggle.
- **Booking Flow**: Date range picker → branch selection (interactive OSM map) → server-side price estimate & availability check → license eligibility pre-check → confirm.
- **Payments**: Chapa checkout (opens the gateway's hosted page) with server-authoritative status polling, plus cash-at-branch.
- **Reservations**: Upcoming/past trips, cancellation with reason, booking timeline & allowed actions.
- **Driver's License**: Submit license details with front/back document upload (multipart).
- **Reviews**: Vehicle reviews, own reviews, create/edit within the allowed window.
- **Notifications, Transactions & Branches**: Paginated notification list with read state, payment history & invoices, branch list/detail with map deep links.
- **Favorites**: Local wishlist (works offline).

## Tech Stack

- **Framework**: Flutter (Dart) 3.x
- **Routing**: `go_router` (auth-guarded routes)
- **HTTP**: `http` with a hardened `ApiClient` (timeouts, typed errors, 401 handling, Laravel 422 mapping)
- **Storage**: `flutter_secure_storage` (tokens), `shared_preferences` (favorites, onboarding flag)
- **Maps**: `flutter_map` + OpenStreetMap tiles (no API key required)

## Getting Started

### 1. Install dependencies

```bash
flutter pub get
```

### 2. Start the backend

The Laravel API must be running and reachable from the device/emulator:

```bash
cd ../car-rental-system-website/backend
php artisan serve --host=0.0.0.0 --port=8000
```

### 3. Run the app with the API base URL

The base URL is injected at build time via `--dart-define` (default: `http://127.0.0.1:8000/api`):

```bash
# Android emulator (uses the host loopback alias)
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api

# USB-connected device + ADB reverse tunnel
adb reverse tcp:8000 tcp:8000
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api

# Physical device on the same Wi-Fi (use your PC's LAN IP)
flutter run --dart-define=API_BASE_URL=http://192.168.X.X:8000/api

# Production (HTTPS only — release builds enforce it)
flutter run --release --dart-define=API_BASE_URL=https://api.yourdomain.com/api
```

The URL can also be set once per machine with `launch.json` / `launchSettings` or in your IDE's run configuration.

### 4. Tests

```bash
flutter test      # unit + widget tests
flutter analyze   # static analysis (must be clean)
```

## Android release notes

- `applicationId` is `com.apexrentals.app` (set in `android/app/build.gradle`).
- **Cleartext HTTP is allowed in debug builds only** (`android/app/src/debug/AndroidManifest.xml`). Release builds must point at an HTTPS API.
- Release signing still uses debug keys — **generate a keystore and configure `signingConfigs.release` before Play Store distribution**:

```bash
keytool -genkey -v -keystore ~/apex-rentals.jks -keyalg RSA -keysize 2048 -validity 10000 -alias apex
```

Then reference it in `android/key.properties` and `build.gradle` (see [Flutter docs: publishing](https://docs.flutter.dev/deployment/android)).

## iOS notes

- Display name: **Apex Rentals**. Camera & photo-library usage descriptions are declared (driver's license uploads).
- ATS allows local networking only; App Store builds must use an HTTPS API.

## Project structure

```
lib/
├── core/             # config (API base URL, endpoints, auth state), theme, routes
├── data/
│   ├── api/          # ApiClient, TokenStorage (secure)
│   ├── models/       # API envelope (ApiResponse / ApiError / pagination)
│   └── repositories/ # Feature repositories (vehicle, booking, payment, ...)
├── models/           # Domain models (Vehicle, Booking, User, Review, ...)
├── screens/          # UI screens grouped by feature
├── widgets/          # Reusable UI components
└── main.dart         # Entry point (loads token before first frame)
```

## Troubleshooting

- **Connection refused / timeout**: The device must reach the backend — use the correct base URL variant from step 3 (emulator vs USB vs Wi-Fi).
- **401 loops / kicked to login**: The token expired or was revoked (e.g. seeding resets the DB). Log in again.
- **Booking rejected with "No driver's license submitted"**: Submit and get the license verified via Profile → Driver's License first.
- **Blank images**: Vehicle images are external URLs (Cloudinary); the backend must return absolute URLs.

---
Part of the Apex Rentals ecosystem.
