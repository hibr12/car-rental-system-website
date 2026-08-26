/// A Laravel database notification.
///
/// The backend returns raw notification models: the top-level `type` is a
/// PHP class name (FQCN) and the friendly `title`, `message` and semantic
/// `type` live inside the JSON `data` column (see e.g.
/// `app/Notifications/BookingConfirmed.php::toArray`). This model therefore
/// parses everything from `data` with safe fallbacks.
class AppNotification {
  final String id;
  final String title;
  final String message;

  /// Semantic type from `data.type`, e.g. `booking_confirmed`,
  /// `payment_success`, `license_approved`.
  final String semanticType;
  final DateTime? readAt;
  final Map<String, dynamic> data;
  final DateTime createdAt;

  /// Entity ids commonly present in notification payloads.
  final int? bookingId;
  final int? paymentId;
  final int? vehicleId;

  AppNotification({
    required this.id,
    required this.title,
    required this.message,
    required this.semanticType,
    this.readAt,
    required this.data,
    required this.createdAt,
    this.bookingId,
    this.paymentId,
    this.vehicleId,
  });

  bool get isRead => readAt != null;

  /// Icon hint derived from the semantic type (kept coarse on purpose).
  bool get isPaymentRelated =>
      semanticType.contains('payment') || semanticType.contains('refund');
  bool get isLicenseRelated => semanticType.contains('license');
  bool get isReviewRelated => semanticType.contains('review');

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    final data =
        json['data'] is Map<String, dynamic> ? json['data'] as Map<String, dynamic> : <String, dynamic>{};

    // Fallback chain for the type: semantic `data.type` → FQCN tail.
    final rawType = (data['type'] as String?) ?? (json['type'] as String? ?? '');
    var semanticType = rawType;
    if (semanticType.contains('\\')) {
      // "App\Notifications\BookingConfirmed" → "BookingConfirmed"
      semanticType = semanticType.split('\\').last;
    }

    DateTime? tryParse(dynamic value) =>
        value is String && value.isNotEmpty ? DateTime.tryParse(value) : null;

    return AppNotification(
      id: json['id']?.toString() ?? '',
      title: (data['title'] as String?)?.trim().isNotEmpty == true
          ? data['title'] as String
          : _fallbackTitle(semanticType),
      message: (data['message'] as String?)?.trim() ?? '',
      semanticType: semanticType,
      readAt: tryParse(json['read_at']),
      data: data,
      createdAt:
          tryParse(json['created_at']) ?? tryParse(data['created_at']) ?? DateTime.now(),
      bookingId: _parseInt(data['booking_id']),
      paymentId: _parseInt(data['payment_id']),
      vehicleId: _parseInt(data['vehicle_id']),
    );
  }

  static int? _parseInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value);
    return null;
  }

  static String _fallbackTitle(String type) {
    if (type.isEmpty) return 'Notification';
    // "BookingConfirmed" → "Booking Confirmed"
    final spaced = type.replaceAllMapped(
        RegExp(r'([a-z])([A-Z])'), (m) => '${m.group(1)} ${m.group(2)}');
    return spaced.replaceAll('_', ' ').trim();
  }
}
