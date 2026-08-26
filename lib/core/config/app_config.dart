/// Central application configuration.
///
/// All API URLs and tunable network constants live here. Endpoint *paths*
/// themselves are declared in [ApiEndpoints] (see `api_endpoints.dart`).
class AppConfig {
  AppConfig._();

  /// Base URL for the Laravel API (the `/api` prefix is part of it).
  ///
  /// Uses the loopback address of the phone combined with an ADB reverse
  /// tunnel (`adb reverse tcp:8000 tcp:8000`) so a USB-connected device
  /// reaches this PC's dev backend (`php artisan serve --host=0.0.0.0`)
  /// regardless of Wi-Fi network or firewall. Cleartext HTTP is enabled
  /// in AndroidManifest.xml for development only.
  ///
  /// Other options:
  ///  * Same Wi-Fi (physical) → `http://<pc-lan-ip>:8000/api`
  ///  * Android emulator      → `http://10.0.2.2:8000/api`
  ///  * Production            → an HTTPS URL, e.g. `https://api.example.com/api`
  static const String apiBaseUrl = 'http://127.0.0.1:8000/api';

  /// Connection / read timeout for every HTTP request.
  static const Duration timeoutDuration = Duration(seconds: 20);

  /// Default page size used for paginated vehicle listings.
  static const int defaultPageSize = 12;
}
