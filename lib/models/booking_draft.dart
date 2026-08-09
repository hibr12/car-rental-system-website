// ignore_for_file: depend_on_referenced_packages
import 'package:flutter/material.dart';

import 'vehicle_model.dart';

/// A mutable bag of data threaded through the booking flow
/// (date selection → extras → summary → success).
///
/// The Laravel backend only accepts `vehicle_id`, `pickup_location`,
/// `return_location`, `pickup_date`, `return_date` and `notes` when
/// creating a booking; everything else here is for the UI/pricing display.
/// The server computes `number_of_days`, `subtotal` and `total_price`.
class BookingDraft {
  final Vehicle vehicle;
  final DateTime pickupDate;
  final DateTime returnDate;
  final TimeOfDay pickupTime;
  final TimeOfDay returnTime;
  final String pickupLocation;
  final String returnLocation;
  final String notes;

  const BookingDraft({
    required this.vehicle,
    required this.pickupDate,
    required this.returnDate,
    required this.pickupTime,
    required this.returnTime,
    this.pickupLocation = '',
    this.returnLocation = '',
    this.notes = '',
  });

  /// Number of days between pickup and return (inclusive minimum of 1).
  int get numberOfDays {
    final days = returnDate.difference(pickupDate).inDays;
    return days < 1 ? 1 : days;
  }

  /// Rental subtotal = daily rate × number of days.
  double get rentalSubtotal => vehicle.pricePerDay * numberOfDays;

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
    TimeOfDay? pickupTime,
    TimeOfDay? returnTime,
    String? pickupLocation,
    String? returnLocation,
    String? notes,
  }) {
    return BookingDraft(
      vehicle: vehicle ?? this.vehicle,
      pickupDate: pickupDate ?? this.pickupDate,
      returnDate: returnDate ?? this.returnDate,
      pickupTime: pickupTime ?? this.pickupTime,
      returnTime: returnTime ?? this.returnTime,
      pickupLocation: pickupLocation ?? this.pickupLocation,
      returnLocation: returnLocation ?? this.returnLocation,
      notes: notes ?? this.notes,
    );
  }
}
