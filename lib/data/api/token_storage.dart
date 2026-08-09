import 'package:shared_preferences/shared_preferences.dart';

/// Manages persistence of the Sanctum API token using SharedPreferences.
class TokenStorage {
  static const String _tokenKey = 'auth_token';

  /// Save the Sanctum token to local storage.
  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  /// Retrieve the stored Sanctum token, or null if not logged in.
  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  /// Remove the stored token (used on logout).
  static Future<void> deleteToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }
}
