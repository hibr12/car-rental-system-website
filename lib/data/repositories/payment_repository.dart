import '../../core/config/api_endpoints.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

/// Repository for creating payments against bookings.
///
/// Payment **listing** stays in [TransactionRepository] (GET /payments).
/// This repository handles only the **creation** of a new payment.
class PaymentRepository {
  static final PaymentRepository instance = PaymentRepository._internal();
  PaymentRepository._internal();

  final ApiClient _api = ApiClient.instance;

  /// Create a payment for a booking.
  ///
  /// **CRITICAL**: the server validates that [amount] == booking `total_price`.
  /// Pass `booking.totalAmount` as the amount.
  ///
  /// Valid [paymentMethod] values: `cash`, `bank_transfer`, `card`,
  /// `online_payment`.
  Future<ApiResponse<Map<String, dynamic>>> createPayment({
    required String bookingId,
    required double amount,
    required String paymentMethod,
  }) async {
    try {
      final json = await _api.post(ApiEndpoints.payments, body: {
        'booking_id': int.tryParse(bookingId) ?? bookingId,
        'amount': amount,
        'payment_method': paymentMethod,
      });
      final data = json['data'] as Map<String, dynamic>? ?? json;
      return ApiResponse.success(data);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
