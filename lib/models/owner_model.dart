class Owner {
  final String id;
  final String name;
  final String profileImageUrl;
  final double rating;
  final int totalTrips;
  final bool isSuperHost;
  final DateTime memberSince;
  final String responseTime;
  final String responseRate;

  const Owner({
    required this.id,
    required this.name,
    required this.profileImageUrl,
    required this.rating,
    required this.totalTrips,
    this.isSuperHost = false,
    required this.memberSince,
    required this.responseTime,
    required this.responseRate,
  });

  /// Parse an Owner from a Laravel UserResource JSON (used for vehicle creator).
  factory Owner.fromJson(Map<String, dynamic> json) {
    return Owner(
      id: json['id'].toString(),
      name: (json['name'] as String?)?.trim() ?? 'Unknown',
      profileImageUrl: json['profile_photo'] as String? ?? '',
      rating: 5.0, // Not available from the User resource.
      totalTrips: 0,
      isSuperHost: false,
      memberSince: _parseDate(json['created_at']),
      responseTime: 'N/A',
      responseRate: 'N/A',
    );
  }

  /// Default placeholder when no creator data is available from the API.
  factory Owner.placeholder() {
    return Owner(
      id: '0',
      name: 'DriveEase',
      profileImageUrl: '',
      rating: 5.0,
      totalTrips: 0,
      isSuperHost: false,
      memberSince: DateTime.now(),
      responseTime: 'N/A',
      responseRate: 'N/A',
    );
  }

  static DateTime _parseDate(dynamic value) {
    if (value is String && value.isNotEmpty) {
      try {
        return DateTime.parse(value);
      } catch (_) {
        // ignore
      }
    }
    return DateTime.now();
  }
}
