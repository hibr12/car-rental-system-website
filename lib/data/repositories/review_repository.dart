import '../../core/config/api_endpoints.dart';
import '../../models/review_model.dart';
import '../models/api_models.dart';
import '../api/api_client.dart';

class ReviewRepository {
  static final ReviewRepository instance = ReviewRepository._internal();
  ReviewRepository._internal();

  final ApiClient _api = ApiClient.instance;

  Future<ApiResponse<PaginatedResponse<Review>>> getVehicleReviews(
    String vehicleId, {
    int page = 1,
  }) async {
    try {
      final id = int.tryParse(vehicleId) ?? 0;
      final json = await _api.get(
        ApiEndpoints.vehicleReviews(id),
        queryParams: {'page': page.toString()},
      );
      final paginated = PaginatedResponse<Review>.fromJson(
        json,
        (item) => Review.fromJson(item),
      );
      return ApiResponse.success(paginated);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Submit a review for a vehicle.
  ///
  /// Backend requires `rating` as an integer 1–5, `booking_id` to verify
  /// the user has a completed booking for this vehicle, and `comment`.
  Future<ApiResponse<Review>> addReview({
    required String vehicleId,
    required String bookingId,
    required int rating,
    required String comment,
  }) async {
    try {
      final id = int.tryParse(vehicleId) ?? 0;
      final json = await _api.post(ApiEndpoints.vehicleReviews(id), body: {
        'booking_id': int.tryParse(bookingId) ?? bookingId,
        'rating': rating,
        'comment': comment,
      });
      final review = Review.fromJson(json['data'] as Map<String, dynamic>);
      return ApiResponse.success(review);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Delete a review. Only the author can delete their own review.
  Future<ApiResponse<bool>> deleteReview(String reviewId) async {
    try {
      final id = int.tryParse(reviewId) ?? 0;
      await _api.delete(ApiEndpoints.review(id));
      return ApiResponse.success(true);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
