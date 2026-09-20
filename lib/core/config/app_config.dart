/// Central application configuration.
///
/// All API URLs and tunable network constants live here. Endpoint *paths*
/// themselves are declared in [ApiEndpoints] (see `api_endpoints.dart`).
///
/// The API base URL is injected at build time with `--dart-define`, e.g.:
///
///   flutter run \
///     --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
///
/// Common values:
///  * USB device + ADB reverse (`adb reverse tcp:8000 tcp:8000`)
///    → http://127.0.0.1:8000/api
///  * Same Wi-Fi (physical) → http://<pc-lan-ip>:8000/api
///  * Android emulator      → http://10.0.2.2:8000/api
///  * Production            → an HTTPS URL, e.g. https://api.example.com/api
class AppConfig {
  AppConfig._();

  /// Base URL for the Laravel API (the `/api` prefix is part of it).
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000/api',
  );

  /// Connection / read timeout for every HTTP request.
  static const Duration timeoutDuration = Duration(seconds: 20);

  /// Default page size used for paginated vehicle listings.
  static const int defaultPageSize = 12;
}
