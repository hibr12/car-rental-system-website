enum TransactionType { payment, refund, deposit, fee }

enum TransactionStatus {
  pending,
  processing,
  successful,
  failed,
  refunded,
  partiallyRefunded,
  cancelled,
  depositHeld,
  depositReleased
}

class Transaction {
  final String id;
  final String bookingReference;
  final String vehicleName;
  final TransactionType type;
  final TransactionStatus status;
  final double amount;
  final DateTime date;
  final String paymentMethod;
  final String? transactionReference;
  final DateTime? paidAt;
  final String? invoiceUrl;

  const Transaction({
    required this.id,
    required this.bookingReference,
    required this.vehicleName,
    required this.type,
    required this.status,
    required this.amount,
    required this.date,
    required this.paymentMethod,
    this.transactionReference,
    this.paidAt,
    this.invoiceUrl,
  });

  String get typeLabel {
    switch (type) {
      case TransactionType.payment:
        return 'Payment';
      case TransactionType.refund:
        return 'Refund';
      case TransactionType.deposit:
        return 'Security Deposit';
      case TransactionType.fee:
        return 'Service Fee';
    }
  }

  String get statusLabel {
    switch (status) {
      case TransactionStatus.pending:
        return 'Pending';
      case TransactionStatus.processing:
        return 'Processing';
      case TransactionStatus.successful:
        return 'Successful';
      case TransactionStatus.failed:
        return 'Failed';
      case TransactionStatus.refunded:
        return 'Refunded';
      case TransactionStatus.partiallyRefunded:
        return 'Partially Refunded';
      case TransactionStatus.cancelled:
        return 'Cancelled';
      case TransactionStatus.depositReleased:
        return 'Deposit Released';
      case TransactionStatus.depositHeld:
        return 'Deposit Held';
    }
  }

  static TransactionStatus parseStatus(String? status) {
    switch (status?.toLowerCase()) {
      case 'pending':
        return TransactionStatus.pending;
      case 'processing':
        return TransactionStatus.processing;
      case 'completed':
      case 'successful':
      case 'paid':
        return TransactionStatus.successful;
      case 'failed':
        return TransactionStatus.failed;
      case 'refunded':
        return TransactionStatus.refunded;
      case 'cancelled':
        return TransactionStatus.cancelled;
      default:
        return TransactionStatus.pending;
    }
  }

  /// Parse a Transaction from Laravel's PaymentResource JSON.
  ///
  /// PaymentResource fields: id, booking_id, booking (nested), amount,
  /// payment_method, transaction_reference, status, paid_at, created_at,
  /// updated_at.
  ///
  /// NOTE: PaymentResource does NOT nest the vehicle — only `booking`. The
  /// vehicle name is therefore derived from `booking.vehicle` when present,
  /// otherwise we show a generic label (documented in BACKEND_MISSING_FEATURES).
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
      type: TransactionType.payment, // Backend only handles payments today.
      status: parseStatus(json['status'] as String?),
      amount: _parseDouble(json['amount']),
      date: _parseDate(json['created_at']),
      paymentMethod: _humanizePaymentMethod(json['payment_method']),
      transactionReference: json['transaction_reference'] as String?,
      paidAt: _parseNullableDate(json['paid_at']),
      invoiceUrl: null, // Not provided by backend yet (documented).
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
