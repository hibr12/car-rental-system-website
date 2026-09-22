import '../../core/config/api_endpoints.dart';
import '../../models/review_model.dart';
import '../models/api_models.dart';
import '../api/api_client.dart';

/// Repository for the customer review flows.
///
/// Backend rules (ReviewService + ReviewPolicy):
///  * Reviews are always tied to a COMPLETED booking (one per booking).
///  * All five ratings (overall + vehicle/cleanliness/staff/value) are
///    required, each an integer 1–5.
///  * Customers may EDIT their own review within 48h (`is_editable`).
///  * Customers canNOT delete reviews — deletion is staff moderation only,
///    so no delete method exists here on purpose.
class ReviewRepository {
  static final ReviewRepository instance = ReviewRepository._internal();
  ReviewRepository._internal();

  final ApiClient _api = ApiClient.instance;

  /// Public reviews for a vehicle. Paginated; `meta.average_rating` carries
  /// the aggregate when at least one review exists.
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

  /// The authenticated user's own reviews (all statuses), newest first.
  Future<ApiResponse<PaginatedResponse<Review>>> getUserReviews(
      {int page = 1}) async {
    try {
      final json = await _api.get(
        ApiEndpoints.reviews,
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

  /// Completed bookings of the user that have no review yet.
  Future<ApiResponse<List<Map<String, dynamic>>>> getEligibleBookings() async {
    try {
      final json = await _api.get(ApiEndpoints.reviewsEligibleBookings);
      final dataList = json['data'] as List? ?? [];
      final list = dataList.map((e) => e as Map<String, dynamic>).toList();
      return ApiResponse.success(list);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// `GET /bookings/{id}/review-eligibility` →
  /// `{ eligible, already_reviewed?, review_id?, message }`.
  Future<ApiResponse<Map<String, dynamic>>> checkReviewEligibility(
      String bookingId) async {
    try {
      final id = int.tryParse(bookingId) ?? 0;
      final json =
          await _api.get(ApiEndpoints.bookingReviewEligibility(id));
      return ApiResponse.success(json);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Map<String, dynamic> _reviewBody({
    required int overallRating,
    int? vehicleRating,
    int? cleanlinessRating,
    int? staffRating,
    int? valueRating,
    String? comment,
  }) {
    final body = <String, dynamic>{
      'overall_rating': overallRating,
      'vehicle_rating': vehicleRating ?? overallRating,
      'cleanliness_rating': cleanlinessRating ?? overallRating,
      'staff_rating': staffRating ?? overallRating,
      'value_rating': valueRating ?? overallRating,
    };
    if (comment != null && comment.trim().isNotEmpty) {
      body['comment'] = comment.trim();
    }
    return body;
  }

  /// Create a review for a completed booking.
  Future<ApiResponse<Review>> storeForBooking({
    required String bookingId,
    required int overallRating,
    int? vehicleRating,
    int? cleanlinessRating,
    int? staffRating,
    int? valueRating,
    String? comment,
  }) async {
    try {
      final id = int.tryParse(bookingId) ?? 0;
      final body = _reviewBody(
        overallRating: overallRating,
        vehicleRating: vehicleRating,
        cleanlinessRating: cleanlinessRating,
        staffRating: staffRating,
        valueRating: valueRating,
        comment: comment,
      );
      final json = await _api.post(ApiEndpoints.bookingReviews(id), body: body);
      final data = json['data'] as Map<String, dynamic>? ?? json;
      return ApiResponse.success(Review.fromJson(data));
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  /// Update the user's own review — allowed by the backend only within
  /// 48 hours of creation (`is_editable` mirrors this).
  Future<ApiResponse<Review>> updateReview({
    required String reviewId,
    required int overallRating,
    int? vehicleRating,
    int? cleanlinessRating,
    int? staffRating,
    int? valueRating,
    String? comment,
  }) async {
    try {
      final id = int.tryParse(reviewId) ?? 0;
      final body = _reviewBody(
        overallRating: overallRating,
        vehicleRating: vehicleRating,
        cleanlinessRating: cleanlinessRating,
        staffRating: staffRating,
        valueRating: valueRating,
        comment: comment,
      );
      final json = await _api.put(ApiEndpoints.review(id), body: body);
      final data = json['data'] as Map<String, dynamic>? ?? json;
      return ApiResponse.success(Review.fromJson(data));
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
