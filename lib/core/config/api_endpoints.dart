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

  // ── Driver License ──────────────────────────────────────────
  static const String customerLicense = '/customer/license';
  static const String customerLicenseDocuments = '/customer/license/documents';
  static const String customerLicenseEligibility = '/customer/license/eligibility';

  // ── Notifications ────────────────────────────────────────────
  static const String notifications = '/notifications';
  static String notification(dynamic id) => '/notifications/$id';
  static String notificationRead(dynamic id) => '/notifications/$id/read';
  static const String notificationsReadAll = '/notifications/read-all';

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
  
  // ── Booking checks ──────────────────────────────────────────
  static const String checkAvailability = '/bookings/check-availability';
  static const String priceEstimate = '/bookings/price-estimate';

  // ── Payments ───────────────────────────────────────────────────────
  static const String payments = '/payments';

  static String payment(int id) => '/payments/$id';
  
  // ── Payment (Chapa) ──────────────────────────────────────────
  static const String paymentsInitialize = '/payments/initialize';
  static String paymentVerify(String txRef) => '/payments/verify/$txRef';
  static String paymentStatus(int id) => '/payments/$id/status';
  static String bookingPaymentStatus(int id) => '/bookings/$id/payment-status';

  // ── Reviews ────────────────────────────────────────────────────────
  static String review(int id) => '/reviews/$id';
  
  // ── Reviews (additional) ──────────────────────────────────────
  static const String reviews = '/reviews';
  static const String reviewsEligibleBookings = '/reviews/eligible-bookings';
  static String bookingReviewEligibility(int id) => '/bookings/$id/review-eligibility';
  static String bookingReviews(int id) => '/bookings/$id/reviews';

  // ── Branches ─────────────────────────────────────────────────
  static const String branches = '/branches';
  static String branch(int id) => '/branches/$id';
  static String branchReviews(int id) => '/branches/$id/reviews';

  // ── Contact / Support ──────────────────────────────────────────────
  static const String contactMessages = '/contact-messages';
}
