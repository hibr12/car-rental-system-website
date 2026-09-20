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
  /// The Sanctum token is persisted automatically in [TokenStorage].
  Future<ApiResponse<User>> login(String email, String password) async {
    try {
      final json = await _api.post(ApiEndpoints.authLogin, body: {
        'email': email,
        'password': password,
      });
      final data = json['data'] as Map<String, dynamic>;
      final token = data['token'] as String?;
      final userData = data['user'] as Map<String, dynamic>;

      if (token != null && token.isNotEmpty) {
        await TokenStorage.saveToken(token);
      }

      final user = User.fromJson(userData);
      return ApiResponse.success(user, message: token);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Register a new customer.
  ///
  /// The Sanctum token is persisted automatically in [TokenStorage].
  Future<ApiResponse<User>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
  }) async {
    try {
      final json = await _api.post(ApiEndpoints.authRegister, body: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (phone != null && phone.trim().isNotEmpty) 'phone': phone.trim(),
      });
      final data = json['data'] as Map<String, dynamic>;
      final token = data['token'] as String?;
      final userData = data['user'] as Map<String, dynamic>;

      if (token != null && token.isNotEmpty) {
        await TokenStorage.saveToken(token);
      }

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
      await TokenStorage.deleteToken();
      return ApiResponse.error(e.error);
    }
  }

  /// Request password reset link for [email].
  Future<ApiResponse<bool>> forgotPassword(String email) async {
    try {
      final json = await _api.post(
        ApiEndpoints.authForgotPassword,
        body: {'email': email.trim()},
      );
      final message = json['message'] as String? ?? 'Password reset link sent to your email.';
      return ApiResponse.success(true, message: message);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Update the authenticated user's profile.
  Future<ApiResponse<User>> updateProfile(User user) async {
    try {
      final body = <String, dynamic>{
        'name': user.fullName,
        'email': user.email,
        'phone': user.phone.trim().isEmpty ? null : user.phone.trim(),
      };
      final json = await _api.put(ApiEndpoints.authProfile, body: body);
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
    return token != null && token.isNotEmpty;
  }
}
