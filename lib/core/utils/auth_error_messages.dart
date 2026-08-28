import '../../data/models/api_models.dart';

/// Translates [ApiError]s from the auth endpoints into short,
/// customer-friendly messages.
///
/// The API client already converts socket/timeout failures into clean
/// messages; this layer only refines status-code semantics that matter in
/// a login/register context (e.g. Laravel's generic "Invalid credentials"
/// becomes an actionable sentence). Raw exceptions, URLs and stack traces
/// must never reach the UI.
class AuthErrorMessages {
  AuthErrorMessages._();

  static String messageFor(ApiError? error) {
    if (error == null) return 'Something went wrong. Please try again.';

    // Per-field validation problems are shown inline on the form; the
    // banner only needs a summary.
    final validation = error.validationError?.firstMessage;
    if (validation != null && validation.isNotEmpty) return validation;

    switch (error.statusCode) {
      case 400:
        return 'Please check your details and try again.';
      case 401:
        return 'Incorrect email or password. Please try again.';
      case 403:
        return 'This account is not allowed to sign in. Contact support for help.';
      case 422:
        return 'Please check your details and try again.';
      case 429:
        return 'Too many attempts. Please wait a moment and try again.';
    }

    if (error.isNetworkError) {
      return 'No internet connection. Please check your network and try again.';
    }
    if (error.isTimeout) {
      return 'The request timed out. Please check your connection and try again.';
    }
    if (error.statusCode >= 500) {
      return 'Our servers are having trouble right now. Please try again shortly.';
    }

    return 'Something went wrong. Please try again.';
  }
}
