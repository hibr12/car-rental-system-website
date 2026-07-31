/// Central application configuration.
///
/// All API URLs and tunable network constants live here. Endpoint *paths*
/// themselves are declared in [ApiEndpoints] (see `api_endpoints.dart`).
class AppConfig {
  AppConfig._();

  /// Base URL for the Laravel API.
  ///
  /// The backend is served on the development machine's LAN Wi-Fi IP.
  /// Physical devices and emulators on the same Wi-Fi network can reach it.
  /// Note: cleartext HTTP is enabled in `AndroidManifest.xml`.
  static const String apiBaseUrl = 'http://192.168.1.2:8000/api';

  /// Connection / read timeout for every HTTP request.
  static const Duration timeoutDuration = Duration(seconds: 20);

  /// Default page size used for paginated vehicle listings.
  static const int defaultPageSize = 12;
}
