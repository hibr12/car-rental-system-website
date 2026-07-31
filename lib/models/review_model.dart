/// Backend review statuses: pending, approved, rejected.
enum ReviewStatus {
  pending,
  approved,
  rejected;

  static ReviewStatus parse(String? v) {
    switch (v?.toLowerCase()) {
      case 'pending':
        return ReviewStatus.pending;
      case 'approved':
        return ReviewStatus.approved;
      case 'rejected':
        return ReviewStatus.rejected;
      default:
        return ReviewStatus.pending;
    }
  }

  String get label {
    switch (this) {
      case ReviewStatus.pending:
        return 'Pending';
      case ReviewStatus.approved:
        return 'Approved';
      case ReviewStatus.rejected:
        return 'Rejected';
    }
  }
}

class Review {
  final String id;
  final String vehicleId;
  final String bookingId;
  final String userId;
  final String userName;
  final String userProfileImageUrl;
  final double rating;
  final String comment;
  final DateTime date;
  final ReviewStatus status;

  const Review({
    required this.id,
    required this.vehicleId,
    this.bookingId = '',
    this.userId = '',
    required this.userName,
    required this.userProfileImageUrl,
    required this.rating,
    required this.comment,
    required this.date,
    this.status = ReviewStatus.approved,
  });

  /// Parse a Review from Laravel's ReviewResource JSON.
  ///
  /// ReviewResource fields: id, user (nested), vehicle_id, booking_id, rating,
  /// comment, status, created_at, updated_at.
  factory Review.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map<String, dynamic> ? user : <String, dynamic>{};

    return Review(
      id: json['id'].toString(),
      vehicleId: json['vehicle_id']?.toString() ?? '',
      bookingId: json['booking_id']?.toString() ?? '',
      userId: userMap['id']?.toString() ?? '',
      userName: (userMap['name'] as String?)?.trim() ?? 'Anonymous',
      userProfileImageUrl: (userMap['profile_photo'] as String?) ?? '',
      rating: _parseDouble(json['rating']),
      comment: json['comment']?.toString() ?? '',
      date: _parseDate(json['created_at']),
      status: ReviewStatus.parse(json['status'] as String?),
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

  static double _parseDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}
