import '../../core/config/api_endpoints.dart';
import '../../models/user_model.dart';

import '../models/api_models.dart';
import '../api/api_client.dart';
import '../api/token_storage.dart';

class UserRepository {
  static final UserRepository instance = UserRepository._internal();
  UserRepository._internal();

  final ApiClient _api = ApiClient.instance;

  Future<ApiResponse<User>> getCurrentUser() async {
    try {
      final json = await _api.get(ApiEndpoints.authMe);
      final userData = json['data']['user'] as Map<String, dynamic>;
      final user = User.fromJson(userData);
      return ApiResponse.success(user);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Login and return the authenticated user.
  ///
  /// The Sanctum token is persisted automatically. Callers should also
  /// call `AuthState.setToken()` to update the synchronous holder.
  Future<ApiResponse<User>> login(String email, String password) async {
    try {
      final json = await _api.post(ApiEndpoints.authLogin, body: {
        'email': email,
        'password': password,
      });
      final data = json['data'] as Map<String, dynamic>;
      final token = data['token'] as String;
      final userData = data['user'] as Map<String, dynamic>;

      // Persist the Sanctum token
      await TokenStorage.saveToken(token);

      final user = User.fromJson(userData);
      return ApiResponse.success(user, message: token);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Register a new user.
  ///
  /// Returns the user on success. The token is persisted automatically.
  /// The token string is attached to `ApiResponse.message` so callers
  /// can set `AuthState.setToken(response.message!)`.
  Future<ApiResponse<User>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String phone,
  }) async {
    try {
      final json = await _api.post(ApiEndpoints.authRegister, body: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'phone': phone,
      });
      final data = json['data'] as Map<String, dynamic>;
      final token = data['token'] as String;
      final userData = data['user'] as Map<String, dynamic>;

      // Persist the Sanctum token
      await TokenStorage.saveToken(token);

      final user = User.fromJson(userData);
      return ApiResponse.success(user, message: token);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<bool>> logout() async {
    try {
      await _api.post(ApiEndpoints.authLogout);
      await TokenStorage.deleteToken();
      return ApiResponse.success(true);
    } on ApiException catch (e) {
      // Even if the server call fails, clear the local token
      await TokenStorage.deleteToken();
      return ApiResponse.error(e.error);
    }
  }

  /// Update the authenticated user's profile.
  ///
  /// Sends `name`, `email`, and `phone` to `PUT /auth/profile`.
  Future<ApiResponse<User>> updateProfile(User user) async {
    try {
      final json = await _api.put(ApiEndpoints.authProfile, body: {
        'name': user.fullName,
        'email': user.email,
        'phone': user.phone,
      });
      final userData = json['data']['user'] as Map<String, dynamic>;
      final updatedUser = User.fromJson(userData);
      return ApiResponse.success(updatedUser);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Check if the user is currently authenticated (has a stored token).
  Future<bool> isAuthenticated() async {
    final token = await TokenStorage.getToken();
    return token != null;
  }
}
