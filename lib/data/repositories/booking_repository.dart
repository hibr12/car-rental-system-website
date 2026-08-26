import '../../core/config/api_endpoints.dart';
import '../../models/booking_draft.dart';
import '../../models/booking_model.dart';
import '../../models/price_estimate_model.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

class BookingRepository {
  static final BookingRepository instance = BookingRepository._internal();
  BookingRepository._internal();

  final ApiClient _api = ApiClient.instance;

  /// Fetch the current user's bookings.
  ///
  /// The backend scopes non-admin users to their own bookings automatically
  /// (`user_id` filter is implicit) and pages at 15 per page. This method
  /// follows the `meta.last_page` cursor so the customer always sees their
  /// full history.
  Future<ApiResponse<List<Booking>>> getUserBookings() async {
    try {
      final all = <Booking>[];
      var page = 1;
      int lastPage = 1;

      do {
        final json = await _api
            .get(ApiEndpoints.bookings, queryParams: {'page': '$page'});
        final paginated = PaginatedResponse<Booking>.fromJson(
          json,
          (item) => Booking.fromJson(item),
        );
        all.addAll(paginated.data);
        lastPage = paginated.lastPage;
        page++;
      } while (page <= lastPage && page <= 20); // hard safety cap

      return ApiResponse.success(all);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Fetch a single booking by id.
  Future<ApiResponse<Booking>> getBookingById(String id) async {
    try {
      final json = await _api.get(ApiEndpoints.booking(int.tryParse(id) ?? 0));
      final bookingData = json['data'] as Map<String, dynamic>;
      final booking = Booking.fromJson(bookingData);
      return ApiResponse.success(booking);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Create a booking from a [BookingDraft].
  ///
  /// Only the fields accepted by `StoreBookingRequest` are sent:
  /// `vehicle_id`, `pickup_location`, `return_location`, `pickup_date`,
  /// `return_date` (+ optional `branch_id`, `notes`). The server computes
  /// pricing and validates license eligibility.
  Future<ApiResponse<Booking>> createBooking(BookingDraft draft) async {
    try {
      final body = <String, dynamic>{
        'vehicle_id': int.tryParse(draft.vehicle.id) ?? draft.vehicle.id,
        'pickup_location': draft.pickupLocation.isNotEmpty
            ? draft.pickupLocation
            : draft.vehicle.location,
        'return_location': draft.returnLocation.isNotEmpty
            ? draft.returnLocation
            : draft.vehicle.location,
        'pickup_date': draft.pickupDateIso,
        'return_date': draft.returnDateIso,
        if (draft.notes.isNotEmpty) 'notes': draft.notes,
      };

      final json = await _api.post(ApiEndpoints.bookings, body: body);
      final bookingData = json['data'] as Map<String, dynamic>;
      final created = Booking.fromJson(bookingData);
      return ApiResponse.success(created);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Cancel a booking. The backend accepts an optional `reason`
  /// (`CancelBookingRequest`: nullable, max 500 chars) and only allows
  /// cancelling while the booking is in a cancellable state.
  Future<ApiResponse<bool>> cancelBooking(String bookingId,
      {String? reason}) async {
    try {
      await _api.put(
        ApiEndpoints.cancelBooking(int.tryParse(bookingId) ?? 0),
        body: {
          if (reason != null && reason.trim().isNotEmpty)
            'reason': reason.trim(),
        },
      );
      return ApiResponse.success(true);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// `GET /bookings/check-availability` — responds `{ available: bool }`.
  Future<ApiResponse<bool>> checkAvailability({
    required String vehicleId,
    required DateTime pickupDate,
    required DateTime returnDate,
  }) async {
    try {
      final json = await _api.get(ApiEndpoints.checkAvailability, queryParams: {
        'vehicle_id': vehicleId,
        'pickup_date': _dateOnly(pickupDate),
        'return_date': _dateOnly(returnDate),
      });
      final data = json['data'] as Map<String, dynamic>? ?? json;
      final available = data['available'] as bool? ?? false;
      return ApiResponse.success(available);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// `GET /bookings/price-estimate` — authoritative server-side pricing.
  Future<ApiResponse<PriceEstimate>> getPriceEstimate({
    required String vehicleId,
    required DateTime pickupDate,
    required DateTime returnDate,
  }) async {
    try {
      final json = await _api.get(ApiEndpoints.priceEstimate, queryParams: {
        'vehicle_id': vehicleId,
        'pickup_date': _dateOnly(pickupDate),
        'return_date': _dateOnly(returnDate),
      });
      final data = json['data'] as Map<String, dynamic>? ?? json;
      return ApiResponse.success(PriceEstimate.fromJson(data));
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  static String _dateOnly(DateTime d) =>
      d.toIso8601String().split('T').first;
}
