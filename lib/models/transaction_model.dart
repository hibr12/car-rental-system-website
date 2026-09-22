/// Payment statuses from `Payment::STATUSES` (PaymentResource `status`).
enum TransactionStatus {
  unpaid,
  pending,
  processing,
  cashPending,
  paid,
  failed,
  cancelled,
  expired,
  invalid,
  refundPending,
  partiallyRefunded,
  refunded,
  disputed;

  static TransactionStatus parse(String? status) {
    switch (status?.toLowerCase()) {
      case 'unpaid':
        return TransactionStatus.unpaid;
      case 'pending':
        return TransactionStatus.pending;
      case 'processing':
        return TransactionStatus.processing;
      case 'cash_pending':
        return TransactionStatus.cashPending;
      case 'paid':
        return TransactionStatus.paid;
      case 'failed':
        return TransactionStatus.failed;
      case 'cancelled':
        return TransactionStatus.cancelled;
      case 'expired':
        return TransactionStatus.expired;
      case 'invalid':
        return TransactionStatus.invalid;
      case 'refund_pending':
        return TransactionStatus.refundPending;
      case 'partially_refunded':
        return TransactionStatus.partiallyRefunded;
      case 'refunded':
        return TransactionStatus.refunded;
      case 'disputed':
        return TransactionStatus.disputed;
      default:
        return TransactionStatus.pending;
    }
  }

  String get label {
    switch (this) {
      case TransactionStatus.unpaid:
        return 'Unpaid';
      case TransactionStatus.pending:
        return 'Pending';
      case TransactionStatus.processing:
        return 'Processing';
      case TransactionStatus.cashPending:
        return 'Cash – Pending';
      case TransactionStatus.paid:
        return 'Paid';
      case TransactionStatus.failed:
        return 'Failed';
      case TransactionStatus.cancelled:
        return 'Cancelled';
      case TransactionStatus.expired:
        return 'Expired';
      case TransactionStatus.invalid:
        return 'Invalid';
      case TransactionStatus.refundPending:
        return 'Refund Pending';
      case TransactionStatus.partiallyRefunded:
        return 'Partially Refunded';
      case TransactionStatus.refunded:
        return 'Refunded';
      case TransactionStatus.disputed:
        return 'Disputed';
    }
  }

  bool get isSuccessful => this == TransactionStatus.paid;

  bool get isRefundFamily =>
      this == TransactionStatus.refundPending ||
      this == TransactionStatus.partiallyRefunded ||
      this == TransactionStatus.refunded;
}

class Transaction {
  final String id;
  final String bookingReference;
  final String vehicleName;
  final TransactionStatus status;

  /// Always ETB (backend hardcodes it); kept for display completeness.
  final String currency;
  final double amount;
  final DateTime date;
  final String paymentMethod;
  final String? transactionReference;
  final DateTime? paidAt;
  final String? failureReason;

  const Transaction({
    required this.id,
    required this.bookingReference,
    required this.vehicleName,
    required this.status,
    this.currency = 'ETB',
    required this.amount,
    required this.date,
    required this.paymentMethod,
    this.transactionReference,
    this.paidAt,
    this.failureReason,
  });

  /// Parse a Transaction from Laravel's PaymentResource JSON.
  factory Transaction.fromJson(Map<String, dynamic> json) {
    final booking = json['booking'];
    final bookingMap =
        booking is Map<String, dynamic> ? booking : <String, dynamic>{};

    final vehicle = bookingMap['vehicle'];
    final vehicleMap =
        vehicle is Map<String, dynamic> ? vehicle : <String, dynamic>{};

    final brand = (vehicleMap['brand'] as String?)?.trim() ?? '';
    final model = (vehicleMap['model'] as String?)?.trim() ?? '';
    final vehicleName = '$brand $model'.trim();

    return Transaction(
      id: json['id'].toString(),
      bookingReference: (bookingMap['booking_reference'] as String?) ??
          json['booking_id']?.toString() ??
          '',
      vehicleName: vehicleName.isNotEmpty ? vehicleName : 'Vehicle',
      status: TransactionStatus.parse(json['status'] as String?),
      currency: json['currency'] as String? ?? 'ETB',
      amount: _parseDouble(json['amount']),
      date: _parseDate(json['created_at']),
      paymentMethod: _humanizePaymentMethod(json['payment_method']),
      transactionReference: json['transaction_reference'] as String?,
      paidAt: _parseNullableDate(json['paid_at']),
      failureReason: json['failure_reason'] as String?,
    );
  }

  /// Convert backend payment_method enum (cash, bank_transfer, card,
  /// online_payment) to a human-friendly label.
  static String _humanizePaymentMethod(dynamic value) {
    final raw = value?.toString() ?? '';
    switch (raw.toLowerCase()) {
      case 'cash':
        return 'Cash';
      case 'bank_transfer':
        return 'Bank Transfer';
      case 'card':
        return 'Card';
      case 'online_payment':
        return 'Online Payment';
      case '':
        return 'N/A';
      default:
        return raw;
    }
  }

  static DateTime _parseDate(dynamic value) {
    if (value is String && value.isNotEmpty) {
      try {
        return DateTime.parse(value);
      } catch (_) {
        // ignore — fall through to default
      }
    }
    return DateTime.now();
  }

  static DateTime? _parseNullableDate(dynamic value) {
    if (value is String && value.isNotEmpty) {
      try {
        return DateTime.parse(value);
      } catch (_) {
        // ignore
      }
    }
    return null;
  }

  static double _parseDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}
