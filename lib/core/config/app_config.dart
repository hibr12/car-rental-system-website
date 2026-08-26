/// Central application configuration.
///
/// All API URLs and tunable network constants live here. Endpoint *paths*
/// themselves are declared in [ApiEndpoints] (see `api_endpoints.dart`).
class AppConfig {
  AppConfig._();

  /// Base URL for the Laravel API (the `/api` prefix is part of it).
  ///
  /// Currently points at this PC's Wi-Fi LAN IP so a physical phone on the
  /// same network can reach the dev backend (`php artisan serve --host=0.0.0.0`).
  /// Cleartext HTTP is enabled in AndroidManifest.xml for development only.
  ///
  /// Other options:
  ///  * Android emulator  → `http://10.0.2.2:8000/api`
  ///  * Production        → an HTTPS URL, e.g. `https://api.example.com/api`
  static const String apiBaseUrl = 'http://192.168.1.9:8000/api';

  /// Connection / read timeout for every HTTP request.
  static const Duration timeoutDuration = Duration(seconds: 20);

  /// Default page size used for paginated vehicle listings.
  static const int defaultPageSize = 12;
}
