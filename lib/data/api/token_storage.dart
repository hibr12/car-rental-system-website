import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Manages persistence of the Sanctum API token.
///
/// The token is stored in [FlutterSecureStorage] (Android Keystore /
/// EncryptedSharedPreferences). Any token left in SharedPreferences by an
/// earlier app version is migrated once and then removed from plaintext.
class TokenStorage {
  static const String _tokenKey = 'auth_token';
  static const _secure = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  /// Save the Sanctum token.
  static Future<void> saveToken(String token) async {
    await _secure.write(key: _tokenKey, value: token);
  }

  /// Retrieve the stored Sanctum token, or null if not logged in.
  static Future<String?> getToken() async {
    String? token = await _secure.read(key: _tokenKey);

    // One-time migration from the legacy plaintext storage.
    if (token == null || token.isEmpty) {
      try {
        final prefs = await SharedPreferences.getInstance();
        final legacy = prefs.getString(_tokenKey);
        if (legacy != null && legacy.isNotEmpty) {
          await _secure.write(key: _tokenKey, value: legacy);
          await prefs.remove(_tokenKey);
          token = legacy;
        }
      } catch (_) {
        // SharedPreferences unavailable — ignore, secure storage wins.
      }
    }
    return token;
  }

  /// Remove the stored token (used on logout).
  static Future<void> deleteToken() async {
    await _secure.delete(key: _tokenKey);
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_tokenKey);
    } catch (_) {
      // ignore
    }
  }
}
