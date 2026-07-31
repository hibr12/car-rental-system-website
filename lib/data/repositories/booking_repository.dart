import '../../core/config/api_endpoints.dart';
import '../../models/booking_draft.dart';
import '../../models/booking_model.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

class BookingRepository {
  static final BookingRepository instance = BookingRepository._internal();
  BookingRepository._internal();

  final ApiClient _api = ApiClient.instance;

  /// Fetch the current user's bookings.
  ///
  /// The backend scopes non-admin users to their own bookings automatically,
  /// so no `user_id` filter is required.
  Future<ApiResponse<List<Booking>>> getUserBookings() async {
    try {
      final json = await _api.get(ApiEndpoints.bookings);
      final dataList = json['data'] as List? ?? [];
      final bookings = dataList
          .map((item) => Booking.fromJson(item as Map<String, dynamic>))
          .toList();
      return ApiResponse.success(bookings);
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
  /// `return_date`, `notes`. The server computes pricing.
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

  /// Cancel a booking (sets status to `cancelled`).
  Future<ApiResponse<bool>> cancelBooking(String bookingId) async {
    try {
      await _api.put(ApiEndpoints.cancelBooking(int.tryParse(bookingId) ?? 0));
      return ApiResponse.success(true);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
