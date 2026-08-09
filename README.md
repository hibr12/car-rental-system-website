# DriveEase Enterprise - Mobile App

DriveEase is a premium, full-featured car rental mobile application built with Flutter. It connects to a Laravel-powered backend API to provide a seamless, production-ready vehicle rental experience.

## Features

- **User Authentication**: Secure login, registration, and session management using Laravel Sanctum.
- **Vehicle Browsing**: Browse featured and popular vehicles, filter by categories (Luxury, SUV, Electric, Economy), and search for specific cars.
- **Booking & Reservations**: Complete end-to-end booking flows including pickup/return dates, pricing breakdown, and reservation management (Upcoming & Past trips).
- **Transaction History**: View detailed payment histories and past transactions directly from the profile.
- **Reviews & Ratings**: View authentic reviews for each vehicle and leave your own feedback after a trip.
- **Local Persistence**: Save your favorite vehicles and preferred addresses locally using `shared_preferences` for quick access, even without an active internet connection.
- **Profile Management**: Update your personal details and manage your account seamlessly.
- **Graceful Degradation**: Features still under development (like Rewards and physical Branches) gracefully display "Coming Soon" states rather than breaking the user experience.

## Tech Stack

- **Framework**: Flutter (Dart)
- **State Management & Routing**: `go_router` for deep linking and navigation.
- **API Integration**: REST API integration using standard Dart HTTP clients.
- **Local Storage**: `shared_preferences` for offline data persistence.
- **Backend**: Laravel API (required for data population and authentication).

## Prerequisites

Before running the application, ensure you have the following installed:

1. **Flutter SDK**: [Install Flutter](https://docs.flutter.dev/get-started/install) (Version 3.x+ recommended).
2. **DriveEase Backend**: Ensure the Laravel backend API is running and accessible on your local network.

## Getting Started

Follow these steps to run the application on your local machine or physical device:

### 1. Clone the Repository
```bash
git clone <repository-url>
cd mobile
```

### 2. Install Dependencies
```bash
flutter pub get
```

### 3. Configure the Backend URL
To connect the app to your Laravel backend, you must update the API endpoint to match your computer's local IP address.

1. Find your computer's local IP address (e.g., `192.168.1.8`).
2. Open `lib/data/api/api_client.dart`.
3. Update the base URL to point to your backend:
   ```dart
   static const String baseUrl = 'http://YOUR_LOCAL_IP:8000/api';
   ```

### 4. Run the Backend
Ensure your Laravel server is bound to `0.0.0.0` so it can receive connections from your physical device:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 5. Run the App
Connect your physical device (ensuring it's on the same Wi-Fi network as your computer) or start an emulator, then run:
```bash
flutter run
```

## Project Structure

The project is organized in a modular, feature-based architecture to ensure maintainability:

```
lib/
├── core/             # App-wide configurations (colors, typography, spacing, routes)
├── data/             # Repositories and API client for backend communication
│   ├── api/          # Base API client and token storage
│   ├── models/       # Shared API response models
│   └── repositories/ # Feature-specific data handlers (Vehicle, User, Booking)
├── mock_data/        # (Deprecated) Old mock data structures
├── models/           # Domain models (User, Vehicle, Booking, etc.)
├── screens/          # UI Screens grouped by feature (auth, home, booking, profile)
├── widgets/          # Reusable UI components (buttons, cards, inputs)
└── main.dart         # Application entry point
```

## Troubleshooting

- **Connection Refused / Timeout**: Ensure your phone and computer are on the exact same Wi-Fi network. Check that you used your LAN IP (e.g., `192.168.X.X`), not `localhost` or `127.0.0.1`, in the `api_client.dart`.
- **Blank Images**: Ensure the image URLs returned by the backend are complete, absolute URLs (including the IP address), rather than relative paths.

---
*Developed as part of the DriveEase Enterprise ecosystem.*
