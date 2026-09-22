import 'vehicle_model.dart';

/// Booking statuses emitted by the backend `BookingResource` (`normalizeStatus`).
///
/// Full enum from `app/Models/Booking.php::STATUSES`.
enum BookingStatus {
  pendingPayment('pending_payment'),
  paymentRequired('payment_required'),
  paymentProcessing('payment_processing'),
  paymentVerified('payment_verified'),
  pendingBranchApproval('pending_branch_approval'),
  pendingAdminApproval('pending_admin_approval'),
  confirmed('confirmed'),
  readyForPickup('ready_for_pickup'),
  active('active'),
  returnPending('return_pending'),
  completed('completed'),
  cancelled('cancelled'),
  rejected('rejected'),
  expired('expired');

  final String value;
  const BookingStatus(this.value);

  /// Parses any status string the backend may emit (including the legacy
  /// `pending` / `branch_review` aliases).
  static BookingStatus parse(String? raw) {
    switch (raw?.toLowerCase()) {
      case 'pending_payment':
      case 'pending': // legacy alias
        return BookingStatus.pendingPayment;
      case 'payment_required':
        return BookingStatus.paymentRequired;
      case 'payment_processing':
        return BookingStatus.paymentProcessing;
      case 'payment_verified':
        return BookingStatus.paymentVerified;
      case 'pending_branch_approval':
        return BookingStatus.pendingBranchApproval;
      case 'pending_admin_approval':
        return BookingStatus.pendingAdminApproval;
      case 'confirmed':
        return BookingStatus.confirmed;
      case 'ready_for_pickup':
        return BookingStatus.readyForPickup;
      case 'active':
        return BookingStatus.active;
      case 'return_pending':
        return BookingStatus.returnPending;
      case 'completed':
        return BookingStatus.completed;
      case 'cancelled':
        return BookingStatus.cancelled;
      case 'rejected':
        return BookingStatus.rejected;
      case 'expired':
        return BookingStatus.expired;
      default:
        return BookingStatus.pendingPayment;
    }
  }

  /// Human label shown in the UI (matches the web's `formatStatus` style).
  String get label {
    switch (this) {
      case BookingStatus.pendingPayment:
        return 'Pending Payment';
      case BookingStatus.paymentRequired:
        return 'Payment Required';
      case BookingStatus.paymentProcessing:
        return 'Payment Processing';
      case BookingStatus.paymentVerified:
        return 'Payment Verified';
      case BookingStatus.pendingBranchApproval:
        return 'Pending Approval';
      case BookingStatus.pendingAdminApproval:
        return 'Pending Approval';
      case BookingStatus.confirmed:
        return 'Confirmed';
      case BookingStatus.readyForPickup:
        return 'Ready for Pickup';
      case BookingStatus.active:
        return 'Active';
      case BookingStatus.returnPending:
        return 'Return Pending';
      case BookingStatus.completed:
        return 'Completed';
      case BookingStatus.cancelled:
        return 'Cancelled';
      case BookingStatus.rejected:
        return 'Rejected';
      case BookingStatus.expired:
        return 'Expired';
    }
  }

  /// Still in the customer's hands — shown under the "Upcoming" tab.
  bool get isUpcoming => !isPast;

  /// Terminal or historical states — shown under the "Past" tab.
  bool get isPast =>
      this == BookingStatus.completed ||
      this == BookingStatus.cancelled ||
      this == BookingStatus.rejected ||
      this == BookingStatus.expired;

  /// Statuses the backend allows customers to cancel
  /// (`Booking::CANCELLABLE_STATUSES`).
  bool get isCancellable =>
      this == BookingStatus.pendingPayment ||
      this == BookingStatus.paymentVerified ||
      this == BookingStatus.pendingBranchApproval ||
      this == BookingStatus.pendingAdminApproval ||
      this == BookingStatus.confirmed ||
      this == BookingStatus.readyForPickup;

  /// Awaiting some form of payment from the customer.
  bool get needsPayment =>
      this == BookingStatus.paymentRequired ||
      this == BookingStatus.pendingPayment;
}

/// `booking.payment_status` values from `Booking::PAYMENT_STATUSES`.
enum PaymentStatus {
  notRequired('not_required'),
  unpaid('unpaid'),
  pending('pending'),
  cashPending('cash_pending'),
  paid('paid'),
  failed('failed'),
  refunded('refunded');

  final String value;
  const PaymentStatus(this.value);

  static PaymentStatus parse(String? v) {
    switch (v?.toLowerCase()) {
      case 'not_required':
        return PaymentStatus.notRequired;
      case 'unpaid':
        return PaymentStatus.unpaid;
      case 'pending':
        return PaymentStatus.pending;
      case 'cash_pending':
        return PaymentStatus.cashPending;
      case 'paid':
        return PaymentStatus.paid;
      case 'failed':
        return PaymentStatus.failed;
      case 'refunded':
        return PaymentStatus.refunded;
      default:
        return PaymentStatus.unpaid;
    }
  }

  String get label {
    switch (this) {
      case PaymentStatus.notRequired:
        return 'Not Required';
      case PaymentStatus.unpaid:
        return 'Unpaid';
      case PaymentStatus.pending:
        return 'Pending';
      case PaymentStatus.cashPending:
        return 'Cash – Pending Confirmation';
      case PaymentStatus.paid:
        return 'Paid';
      case PaymentStatus.failed:
        return 'Failed';
      case PaymentStatus.refunded:
        return 'Refunded';
    }
  }

  bool get isPaid => this == PaymentStatus.paid || this == PaymentStatus.refunded;

  /// Customer still needs to act (pay online or wait for cash confirmation).
  bool get requiresCustomerAction =>
      this == PaymentStatus.unpaid ||
      this == PaymentStatus.pending ||
      this == PaymentStatus.failed;
}

class Booking {
  final String id;
  final Vehicle vehicle;
  final DateTime pickupDate;
  final DateTime returnDate;
  final String pickupLocation;
  final String returnLocation;
  final int numberOfDays;
  final double pricePerDay;
  final double subtotal;
  final double additionalCharges;
  final double discount;
  final double totalAmount;
  final BookingStatus status;
  final PaymentStatus paymentStatus;
  final String bookingReference;
  final String notes;
  final String branchId;
  final String branchName;
  final String? rejectionReason;
  final String? cancellationReason;
  final DateTime? cancelledAt;
  final DateTime? pickedUpAt;
  final DateTime? returnedAt;
  final bool hasReview;
  final String? reviewId;

  const Booking({
    required this.id,
    required this.vehicle,
    required this.pickupDate,
    required this.returnDate,
    required this.pickupLocation,
    required this.returnLocation,
    this.numberOfDays = 0,
    this.pricePerDay = 0,
    this.subtotal = 0,
    this.additionalCharges = 0,
    this.discount = 0,
    required this.totalAmount,
    required this.status,
    this.paymentStatus = PaymentStatus.unpaid,
    required this.bookingReference,
    this.notes = '',
    this.branchId = '',
    this.branchName = '',
    this.rejectionReason,
    this.cancellationReason,
    this.cancelledAt,
    this.pickedUpAt,
    this.returnedAt,
    this.hasReview = false,
    this.reviewId,
  });

  int get durationInDays => numberOfDays > 0
      ? numberOfDays
      : returnDate.difference(pickupDate).inDays;

  /// Whether the customer can start an online (Chapa) payment for this booking.
  ///
  /// Mirrors the backend's `next_action` logic: payment is expected while the
  /// booking is waiting for money and no successful payment exists yet.
  bool get canPayOnline =>
      !status.isPast &&
      (status.needsPayment || status == BookingStatus.paymentVerified) &&
      !paymentStatus.isPaid &&
      paymentStatus != PaymentStatus.cashPending &&
      status != BookingStatus.paymentProcessing;

  /// Whether the customer can pick "Pay with cash at branch" instead.
  bool get canPayCash => canPayOnline && paymentStatus != PaymentStatus.cashPending;

  factory Booking.fromJson(Map<String, dynamic> json) {
    Vehicle vehicle;
    try {
      if (json['vehicle'] != null && json['vehicle'] is Map) {
        vehicle = Vehicle.fromJson(json['vehicle'] as Map<String, dynamic>);
      } else {
        vehicle = _placeholderVehicle;
      }
    } catch (_) {
      vehicle = _placeholderVehicle;
    }

    final branch = json['branch'];
    final branchMap = branch is Map<String, dynamic> ? branch : <String, dynamic>{};

    final review = json['review'];
    final reviewMap = review is Map<String, dynamic> ? review : null;

    return Booking(
      id: json['id'].toString(),
      vehicle: vehicle,
      pickupDate: _parseDate(json['pickup_date']),
      returnDate: _parseDate(json['return_date']),
      pickupLocation: json['pickup_location'] as String? ?? '',
      returnLocation: json['return_location'] as String? ?? '',
      numberOfDays: (json['number_of_days'] as num?)?.toInt() ?? 0,
      pricePerDay: _parseDouble(json['price_per_day']),
      subtotal: _parseDouble(json['subtotal']),
      additionalCharges: _parseDouble(json['additional_charges']),
      discount: _parseDouble(json['discount']),
      totalAmount: _parseDouble(json['total_price']),
      status: BookingStatus.parse(json['status'] as String?),
      paymentStatus: PaymentStatus.parse(json['payment_status'] as String?),
      bookingReference: json['booking_reference'] as String? ?? '',
      notes: json['notes'] as String? ?? '',
      branchId: (json['branch_id'] ?? branchMap['id'])?.toString() ?? '',
      branchName: branchMap['name'] as String? ?? '',
      rejectionReason: json['rejection_reason'] as String?,
      cancellationReason: json['cancellation_reason'] as String?,
      cancelledAt: _parseNullableDate(json['cancelled_at']),
      pickedUpAt: _parseNullableDate(json['picked_up_at']),
      returnedAt: _parseNullableDate(json['returned_at']),
      hasReview: json['has_review'] as bool? ?? reviewMap != null,
      reviewId: reviewMap?['id']?.toString(),
    );
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

  /// Used when the API response is missing the nested vehicle object.
  static const Vehicle _placeholderVehicle = Vehicle(
    id: '0',
    brand: 'Unknown',
    model: 'Vehicle',
    year: 2024,
    pricePerDay: 0,
    rating: 0,
    reviewCount: 0,
    fuelType: '',
    transmission: '',
    seats: 4,
    color: '',
    location: '',
    description: 'Vehicle details unavailable.',
    imageUrls: [placeholderVehicleImage],
    features: [],
    category: 'Other',
  );
}
