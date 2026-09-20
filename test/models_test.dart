import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/models/booking_model.dart';
import 'package:mobile/models/vehicle_model.dart';

void main() {
  group('BookingStatus', () {
    test('parses all current backend statuses', () {
      expect(BookingStatus.parse('pending_payment'), BookingStatus.pendingPayment);
      expect(BookingStatus.parse('payment_required'), BookingStatus.paymentRequired);
      expect(BookingStatus.parse('payment_processing'), BookingStatus.paymentProcessing);
      expect(BookingStatus.parse('payment_verified'), BookingStatus.paymentVerified);
      expect(BookingStatus.parse('pending_branch_approval'), BookingStatus.pendingBranchApproval);
      expect(BookingStatus.parse('pending_admin_approval'), BookingStatus.pendingAdminApproval);
      expect(BookingStatus.parse('confirmed'), BookingStatus.confirmed);
      expect(BookingStatus.parse('ready_for_pickup'), BookingStatus.readyForPickup);
      expect(BookingStatus.parse('active'), BookingStatus.active);
      expect(BookingStatus.parse('return_pending'), BookingStatus.returnPending);
      expect(BookingStatus.parse('completed'), BookingStatus.completed);
      expect(BookingStatus.parse('cancelled'), BookingStatus.cancelled);
      expect(BookingStatus.parse('rejected'), BookingStatus.rejected);
      expect(BookingStatus.parse('expired'), BookingStatus.expired);
    });

    test('parses legacy aliases', () {
      expect(BookingStatus.parse('pending'), BookingStatus.pendingPayment);
      expect(BookingStatus.parse('branch_review'), BookingStatus.pendingPayment);
    });

    test('falls back safely on unknown or null status', () {
      expect(BookingStatus.parse('nonsense'), BookingStatus.pendingPayment);
      expect(BookingStatus.parse(null), BookingStatus.pendingPayment);
    });

    test('isPast is true only for terminal states', () {
      expect(BookingStatus.completed.isPast, isTrue);
      expect(BookingStatus.cancelled.isPast, isTrue);
      expect(BookingStatus.rejected.isPast, isTrue);
      expect(BookingStatus.expired.isPast, isTrue);

      expect(BookingStatus.active.isPast, isFalse);
      expect(BookingStatus.confirmed.isPast, isFalse);
      expect(BookingStatus.pendingPayment.isPast, isFalse);
    });

    test('isUpcoming is the inverse of isPast', () {
      for (final status in BookingStatus.values) {
        expect(status.isUpcoming, !status.isPast);
      }
    });

    test('isCancellable matches backend CANCELLABLE_STATUSES', () {
      expect(BookingStatus.pendingPayment.isCancellable, isTrue);
      expect(BookingStatus.paymentVerified.isCancellable, isTrue);
      expect(BookingStatus.pendingBranchApproval.isCancellable, isTrue);
      expect(BookingStatus.pendingAdminApproval.isCancellable, isTrue);
      expect(BookingStatus.confirmed.isCancellable, isTrue);
      expect(BookingStatus.readyForPickup.isCancellable, isTrue);

      expect(BookingStatus.active.isCancellable, isFalse);
      expect(BookingStatus.completed.isCancellable, isFalse);
      expect(BookingStatus.cancelled.isCancellable, isFalse);
    });

    test('needsPayment covers payment-awaiting states', () {
      expect(BookingStatus.paymentRequired.needsPayment, isTrue);
      expect(BookingStatus.pendingPayment.needsPayment, isTrue);
      expect(BookingStatus.confirmed.needsPayment, isFalse);
      expect(BookingStatus.completed.needsPayment, isFalse);
    });
  });

  group('Vehicle.fromJson', () {
    final Map<String, dynamic> backendVehicle = {
      'id': 42,
      'brand': 'Toyota',
      'model': 'Land Cruiser',
      'year': 2024,
      'registration_number': 'AA-123',
      'description': 'Spacious SUV',
      'fuel_type': 'diesel',
      'transmission': 'automatic',
      'seats': 7,
      'color': 'white',
      'mileage': 15000,
      'rental_price_per_day': '250.00',
      'status': 'available',
      'featured': true,
      'location': 'Bole Branch',
      'branch_id': 3,
      'category': {'name': 'SUV'},
      'images': [
        {'image_url': 'https://cdn.example.com/1.jpg'},
        {'image_url': 'https://cdn.example.com/2.jpg'},
      ],
    };

    test('parses full backend payload', () {
      final v = Vehicle.fromJson(backendVehicle);

      expect(v.id, '42');
      expect(v.brand, 'Toyota');
      expect(v.model, 'Land Cruiser');
      expect(v.year, 2024);
      expect(v.pricePerDay, 250.0);
      expect(v.seats, 7);
      expect(v.category, 'SUV');
      expect(v.isFeatured, isTrue);
      expect(v.imageUrls, [
        'https://cdn.example.com/1.jpg',
        'https://cdn.example.com/2.jpg',
      ]);
    });

    test('falls back to placeholder image when images missing', () {
      final v = Vehicle.fromJson({
        ...backendVehicle,
        'images': <Map<String, dynamic>>[],
      });

      expect(v.imageUrls, [placeholderVehicleImage]);
    });

    test('uses primary_image when images array is empty', () {
      final v = Vehicle.fromJson({
        ...backendVehicle,
        'images': <Map<String, dynamic>>[],
        'primary_image': {'image_url': 'https://cdn.example.com/primary.jpg'},
      });

      expect(v.imageUrls, ['https://cdn.example.com/primary.jpg']);
    });

    test('never crashes on minimal payload', () {
      final v = Vehicle.fromJson({
        'id': 1,
        'brand': 'Honda',
        'model': 'Fit',
        'fuel_type': 'petrol',
        'transmission': 'manual',
        'description': '',
        'imageUrls': <String>[],
        'category': 'Economy',
      });

      expect(v.year, 0);
      expect(v.color, '');
      expect(v.imageUrls, [placeholderVehicleImage]);
      expect(v.category, 'Other');
    });
  });
}
