import 'vehicle_model.dart';
import 'owner_model.dart';

/// Backend booking statuses (enum column): pending, confirmed, active,
/// completed, cancelled, rejected.
enum BookingStatus {
  pending,
  confirmed,
  active,
  completed,
  cancelled,
  rejected;

  /// Whether the booking is considered "upcoming" (not yet finished).
  bool get isUpcoming =>
      this == BookingStatus.pending ||
      this == BookingStatus.confirmed ||
      this == BookingStatus.active;

  /// Whether the booking is finished / terminal.
  bool get isPast =>
      this == BookingStatus.completed ||
      this == BookingStatus.cancelled ||
      this == BookingStatus.rejected;
}

/// Backend payment_status enum: unpaid, pending, paid, failed, refunded.
enum PaymentStatus {
  unpaid,
  pending,
  paid,
  failed,
  refunded;

  static PaymentStatus parse(String? v) {
    switch (v?.toLowerCase()) {
      case 'unpaid':
        return PaymentStatus.unpaid;
      case 'pending':
        return PaymentStatus.pending;
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
      case PaymentStatus.pending:
        return 'Pending';
      case PaymentStatus.paid:
        return 'Paid';
      case PaymentStatus.unpaid:
        return 'Unpaid';
      case PaymentStatus.failed:
        return 'Failed';
      case PaymentStatus.refunded:
        return 'Refunded';
    }
  }

  bool get isPaid =>
      this == PaymentStatus.paid || this == PaymentStatus.refunded;
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
  });

  int get durationInDays => returnDate.difference(pickupDate).inDays;

  String get statusLabel {
    switch (status) {
      case BookingStatus.pending:
        return 'Pending';
      case BookingStatus.confirmed:
        return 'Confirmed';
      case BookingStatus.active:
        return 'Active';
      case BookingStatus.completed:
        return 'Completed';
      case BookingStatus.cancelled:
        return 'Cancelled';
      case BookingStatus.rejected:
        return 'Rejected';
    }
  }

  /// Parse a BookingStatus from a Laravel status string (case-insensitive).
  static BookingStatus parseStatus(String? status) {
    switch (status?.toLowerCase()) {
      case 'pending':
        return BookingStatus.pending;
      case 'confirmed':
        return BookingStatus.confirmed;
      case 'active':
        return BookingStatus.active;
      case 'completed':
        return BookingStatus.completed;
      case 'cancelled':
        return BookingStatus.cancelled;
      case 'rejected':
        return BookingStatus.rejected;
      default:
        return BookingStatus.pending;
    }
  }

  /// Parse a Booking from Laravel's BookingResource JSON.
  ///
  /// If the nested `vehicle` is missing or malformed, a placeholder vehicle
  /// is used so one bad record cannot blank the entire booking list.
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
      status: parseStatus(json['status'] as String?),
      paymentStatus: PaymentStatus.parse(json['payment_status'] as String?),
      bookingReference: json['booking_reference'] as String? ?? '',
      notes: json['notes'] as String? ?? '',
    );
  }

  /// Safe date parser — falls back to [now] instead of throwing.
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

  static double _parseDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }

  /// Used when the API response is missing the nested vehicle object.
  static final Vehicle _placeholderVehicle = Vehicle(
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
    owner: Owner.placeholder(),
  );
}
