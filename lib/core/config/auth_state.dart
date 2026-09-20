import '../../data/api/api_client.dart';
import '../../data/api/token_storage.dart';
import '../../data/repositories/user_repository.dart';
import '../routes/app_routes.dart';

/// Lightweight, synchronous auth state holder.
///
/// Populated once during app bootstrap (`main.dart`) from
/// [TokenStorage]. Updated by login/register (set token) and
/// logout / 401 (clear token).
class AuthState {
  AuthState._();

  static bool get isAuthenticated => _token.isNotEmpty;

  static String get token => _token;

  static String _token = '';

  /// Called once in `main.dart` before `runApp`.
  static Future<void> init() async {
    _token = await TokenStorage.getToken() ?? '';
  }

  /// Call after successful login / register.
  static Future<void> setToken(String newToken) async {
    _token = newToken;
    await TokenStorage.saveToken(newToken);
  }

  /// Call after logout or 401.
  static Future<void> clear() async {
    _token = '';
    await TokenStorage.deleteToken();
  }

  /// Wire the API client's 401 callback: clear the session and send the
  /// user to login. Skips re-navigation when already on a public screen
  /// (e.g. a failed login attempt also produces a 401).
  static void initApiClientCallback() {
    ApiClient.instance.onUnauthorized = () {
      final wasAuthenticated = _token.isNotEmpty;
      _token = '';
      TokenStorage.deleteToken();
      if (wasAuthenticated) {
        AppRoutes.router.go(AppRoutes.login);
      }
    };
  }

  /// Checks a persisted token against the server (`GET /auth/me`).
  static Future<bool> validateSession() async {
    if (!isAuthenticated) return false;
    final res = await UserRepository.instance.getCurrentUser();
    if (res.success) return true;
    final status = res.error?.statusCode ?? 0;
    if (status == 401 || status == 403) {
      await clear();
      return false;
    }
    return true;
  }
}
