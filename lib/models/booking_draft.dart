import 'vehicle_model.dart';

/// A mutable bag of data threaded through the booking flow
/// (date selection → summary → success).
///
/// Only `vehicle_id`, `pickup_location`, `return_location`, `pickup_date`,
/// `return_date` (+ optional `branch_id`, `notes`) are sent to the backend;
/// it computes pricing and validates availability/eligibility. The backend
/// has no time-of-day component, so none is collected.
class BookingDraft {
  final Vehicle vehicle;
  final DateTime pickupDate;
  final DateTime returnDate;
  final String pickupLocation;
  final String returnLocation;
  final String notes;

  const BookingDraft({
    required this.vehicle,
    required this.pickupDate,
    required this.returnDate,
    this.pickupLocation = '',
    this.returnLocation = '',
    this.notes = '',
  });

  /// Number of days between pickup and return (inclusive minimum of 1).
  int get numberOfDays {
    final days = returnDate.difference(pickupDate).inDays;
    return days < 1 ? 1 : days;
  }

  /// ISO date strings expected by the backend (`YYYY-MM-DD`).
  String get pickupDateIso => _toIsoDate(pickupDate);
  String get returnDateIso => _toIsoDate(returnDate);

  static String _toIsoDate(DateTime d) {
    final y = d.year.toString().padLeft(4, '0');
    final m = d.month.toString().padLeft(2, '0');
    final day = d.day.toString().padLeft(2, '0');
    return '$y-$m-$day';
  }

  BookingDraft copyWith({
    Vehicle? vehicle,
    DateTime? pickupDate,
    DateTime? returnDate,
    String? pickupLocation,
    String? returnLocation,
    String? notes,
  }) {
    return BookingDraft(
      vehicle: vehicle ?? this.vehicle,
      pickupDate: pickupDate ?? this.pickupDate,
      returnDate: returnDate ?? this.returnDate,
      pickupLocation: pickupLocation ?? this.pickupLocation,
      returnLocation: returnLocation ?? this.returnLocation,
      notes: notes ?? this.notes,
    );
  }
}
