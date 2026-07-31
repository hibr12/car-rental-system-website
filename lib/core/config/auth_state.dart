import 'package:flutter/material.dart';

import '../../data/api/api_client.dart';
import '../../data/api/token_storage.dart';

/// Lightweight, synchronous auth state holder.
///
/// Populated once during app bootstrap (`main.dart`) from
/// [TokenStorage]. Updated by login/register (set token) and
/// logout / 401 (clear token).
///
/// Used by the GoRouter `redirect` guard and the splash screen
/// to decide whether to show the home screen or the onboarding/login flow.
class AuthState {
  AuthState._();

  /// Whether a persisted token exists (does NOT validate it against the
  /// server — that check happens lazily when `GET /auth/me` is called).
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

  /// Wire the API client's 401 callback.
  ///
  /// The callback clears the local token *synchronously* so the very next
  /// router redirect (which is synchronous) sees `isAuthenticated == false`.
  /// A real navigation call to `/login` would fail inside the callback
  /// because we don't have a [BuildContext]; instead we rely on the
  /// router's `redirect` guard picking up the cleared state.
  static void initApiClientCallback() {
    ApiClient.instance.onUnauthorized = () {
      _token = '';
      // Fire-and-forget: clear from persistent storage.
      // ignore: unawaited_futures
      TokenStorage.deleteToken();
    };
  }
}
