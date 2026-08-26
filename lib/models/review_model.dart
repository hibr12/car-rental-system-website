/// Review statuses from `ReviewResource` — published, hidden, flagged,
/// archived. (Legacy pending/approved/rejected constants are deprecated
/// server-side and mapped defensively.)
enum ReviewStatus {
  published,
  hidden,
  flagged,
  archived;

  static ReviewStatus parse(String? v) {
    switch (v?.toLowerCase()) {
      case 'published':
      case 'approved': // legacy alias
        return ReviewStatus.published;
      case 'hidden':
        return ReviewStatus.hidden;
      case 'flagged':
        return ReviewStatus.flagged;
      case 'archived':
      case 'rejected': // legacy alias → treated as no longer visible
        return ReviewStatus.archived;
      default:
        return ReviewStatus.published;
    }
  }

  String get label {
    switch (this) {
      case ReviewStatus.published:
        return 'Published';
      case ReviewStatus.hidden:
        return 'Hidden';
      case ReviewStatus.flagged:
        return 'Under Review';
      case ReviewStatus.archived:
        return 'Archived';
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
  final int? vehicleRating;
  final int? cleanlinessRating;
  final int? staffRating;
  final int? valueRating;
  final String comment;
  final String? adminResponse;
  final bool isEditable;
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
    this.vehicleRating,
    this.cleanlinessRating,
    this.staffRating,
    this.valueRating,
    required this.comment,
    this.adminResponse,
    this.isEditable = false,
    required this.date,
    this.status = ReviewStatus.published,
  });

  /// Parse a Review from Laravel's ReviewResource JSON.
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
      rating: _parseDouble(json['overall_rating'] ?? json['rating']),
      vehicleRating: _parseInt(json['vehicle_rating']),
      cleanlinessRating: _parseInt(json['cleanliness_rating']),
      staffRating: _parseInt(json['staff_rating']),
      valueRating: _parseInt(json['value_rating']),
      comment: json['comment']?.toString() ?? '',
      adminResponse: json['admin_response']?.toString(),
      isEditable: json['is_editable'] as bool? ?? false,
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
  
  static int? _parseInt(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value);
    return null;
  }
}
