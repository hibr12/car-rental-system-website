import '../../core/config/api_endpoints.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

/// Repository for sending contact/support messages.
///
/// Uses `POST /contact-messages` which is a **public** endpoint
/// (no auth required).
class ContactRepository {
  static final ContactRepository instance = ContactRepository._internal();
  ContactRepository._internal();

  final ApiClient _api = ApiClient.instance;

  /// Send a contact/support message.
  Future<ApiResponse<bool>> sendMessage({
    required String name,
    required String email,
    required String subject,
    required String message,
  }) async {
    try {
      await _api.post(ApiEndpoints.contactMessages, body: {
        'name': name,
        'email': email,
        'subject': subject,
        'message': message,
      });
      return ApiResponse.success(true);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
