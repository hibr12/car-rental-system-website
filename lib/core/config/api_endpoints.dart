/// Single source of truth for every API path used by the mobile app.
///
/// Every URL is verified against the Laravel backend `routes/api.php`.
/// The base URL (`/api`) is part of [AppConfig.apiBaseUrl], so the values
/// below must NOT include the `/api` prefix — only the resource path.
///
/// IMPORTANT: never inline these strings in repositories/screens; always
/// reference them from here so endpoints stay centralized and discoverable.
class ApiEndpoints {
  ApiEndpoints._();



  // ── Authentication ─────────────────────────────────────────────────
  static const String authRegister = '/auth/register';
  static const String authLogin = '/auth/login';
  static const String authLogout = '/auth/logout';
  static const String authMe = '/auth/me';
  static const String authProfile = '/auth/profile';

  // ── Categories ─────────────────────────────────────────────────────
  static const String categories = '/categories';

  // ── Vehicles ───────────────────────────────────────────────────────
  static const String vehicles = '/vehicles';

  static String vehicle(int id) => '/vehicles/$id';

  static String vehicleReviews(int id) => '/vehicles/$id/reviews';

  // ── Bookings ───────────────────────────────────────────────────────
  static const String bookings = '/bookings';

  static String booking(int id) => '/bookings/$id';

  static String cancelBooking(int id) => '/bookings/$id/cancel';

  // ── Payments ───────────────────────────────────────────────────────
  static const String payments = '/payments';

  static String payment(int id) => '/payments/$id';

  // ── Reviews ────────────────────────────────────────────────────────
  static String review(int id) => '/reviews/$id';

  // ── Contact / Support ──────────────────────────────────────────────
  static const String contactMessages = '/contact-messages';
}
