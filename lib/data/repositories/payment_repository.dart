import '../../core/config/api_endpoints.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

/// Repository for payments.
///
/// Payment **listing** stays in [TransactionRepository] (GET /payments).
/// This repository covers the two customer flows the backend supports:
///
///  * Online (Chapa):  `POST /payments/initialize { booking_id }` →
///    `{ checkout_url, tx_ref, payment }`, then `GET /payments/verify/{tx_ref}`.
///  * Cash:            `POST /payments { booking_id, payment_method: 'cash' }`
///    → creates a `cash_pending` payment that staff confirm in person.
class PaymentRepository {
  static final PaymentRepository instance = PaymentRepository._internal();
  PaymentRepository._internal();

  final ApiClient _api = ApiClient.instance;

  /// Initialize a Chapa online payment for a booking.
  ///
  /// Returns `{ checkout_url, tx_ref, payment }` on success. The backend is
  /// the only holder of Chapa credentials — nothing sensitive crosses here.
  Future<ApiResponse<Map<String, dynamic>>> initializePayment(
      {required String bookingId}) async {
    try {
      final json = await _api.post(ApiEndpoints.paymentsInitialize, body: {
        'booking_id': int.tryParse(bookingId) ?? bookingId,
      });
      final data = json['data'] as Map<String, dynamic>? ?? json;
      return ApiResponse.success(data);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Elect to pay with cash at the branch.
  ///
  /// The backend ignores any client-supplied amount and generates its own
  /// reference; the payment stays `cash_pending` until a staff member
  /// confirms it. Never assume this means "paid".
  Future<ApiResponse<Map<String, dynamic>>> payWithCash(
      {required String bookingId}) async {
    try {
      final json = await _api.post(ApiEndpoints.payments, body: {
        'booking_id': int.tryParse(bookingId) ?? bookingId,
        'payment_method': 'cash',
      });
      final data = json['data'] as Map<String, dynamic>? ?? json;
      return ApiResponse.success(data);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Verify an online payment by its transaction reference after the user
  /// returns from the Chapa checkout. The returned `data` is a
  /// PaymentResource; `status == 'paid'` (+ verified) is the ONLY success
  /// signal — returning from the browser proves nothing by itself.
  ///
  /// A transient gateway outage yields `retryable: true` at HTTP 200.
  Future<ApiResponse<Map<String, dynamic>>> verifyPayment(String txRef) async {
    try {
      final json = await _api.get(ApiEndpoints.paymentVerify(txRef));
      final data = json['data'] is Map<String, dynamic>
          ? Map<String, dynamic>.from(json['data'] as Map)
          : <String, dynamic>{};
      if (json['retryable'] != null) {
        data['retryable'] = json['retryable'];
      }
      return ApiResponse.success(data);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// `GET /payments/{id}/status` — auto-verifies with Chapa while pending.
  Future<ApiResponse<Map<String, dynamic>>> getPaymentStatus(
      String paymentId) async {
    try {
      final json = await _api
          .get(ApiEndpoints.paymentStatus(int.tryParse(paymentId) ?? 0));
      final data = json['data'] as Map<String, dynamic>? ?? json;
      return ApiResponse.success(data);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// `GET /bookings/{id}/payment-status` — authoritative booking-level view;
  /// auto-verifies the latest pending online payment unless `verify: false`.
  Future<ApiResponse<Map<String, dynamic>>> getBookingPaymentStatus(
      String bookingId,
      {bool verify = true}) async {
    try {
      final json = await _api.get(
        ApiEndpoints.bookingPaymentStatus(int.tryParse(bookingId) ?? 0),
        queryParams: {'verify': verify ? 'true' : 'false'},
      );
      final data = json['data'] as Map<String, dynamic>? ?? json;
      return ApiResponse.success(data);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
