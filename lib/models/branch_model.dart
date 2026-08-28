/// A company branch parsed from Laravel's public `/branches` endpoint.
///
/// Serialized fields: `id`, `name`, `address`, `city`, `phone`, `email`,
/// `status` (`active` | `inactive`), optional `manager`, optional
/// `latitude`/`longitude` and `opening_time`/`closing_time`, plus `*_count`
/// values when counted.
///
/// Coordinates are nullable: the database allows branches without them, so
/// the app treats map placement as best-effort and never fabricates a
/// location for a branch that has none.
class Branch {
  final int id;
  final String name;
  final String address;
  final String city;
  final String phone;
  final String email;
  final String status;
  final String? managerName;
  final int vehiclesCount;

  /// Real coordinates from the backend, or null when the branch has none.
  final double? latitude;
  final double? longitude;

  /// Raw `opening_time`/`closing_time` values (`HH:mm` extracted from the
  /// API's ISO datetime), or null when not set.
  final String? openingTime;
  final String? closingTime;

  const Branch({
    required this.id,
    required this.name,
    required this.address,
    required this.city,
    required this.phone,
    required this.email,
    required this.status,
    this.managerName,
    this.vehiclesCount = 0,
    this.latitude,
    this.longitude,
    this.openingTime,
    this.closingTime,
  });

  bool get isActive => status.toLowerCase() == 'active';

  /// Whether the backend provides usable map coordinates for this branch.
  bool get hasLocation =>
      latitude != null && longitude != null && latitude != 0 && longitude != 0;

  bool get hasHours => openingTime != null && closingTime != null;

  /// Single-line location, e.g. "Bole Road, Addis Ababa".
  String get locationLine {
    final parts = [address.trim(), city.trim()]
        .where((p) => p.isNotEmpty)
        .toList(growable: false);
    return parts.join(', ');
  }

  /// "8:00 AM – 6:00 PM" style line built only from real backend data.
  String? get hoursLine {
    if (!hasHours) return null;
    return '${_formatTime(openingTime!)} – ${_formatTime(closingTime!)}';
  }

  static String _formatTime(String hhmm) {
    final parts = hhmm.split(':');
    final h = int.tryParse(parts.first) ?? 0;
    final m = parts.length > 1 ? int.tryParse(parts[1]) ?? 0 : 0;
    final period = h >= 12 ? 'PM' : 'AM';
    final displayH = h % 12 == 0 ? 12 : h % 12;
    final mm = m.toString().padLeft(2, '0');
    return '$displayH:$mm $period';
  }

  factory Branch.fromJson(Map<String, dynamic> json) {
    final manager = json['manager'];
    return Branch(
      id: json['id'] is int
          ? json['id'] as int
          : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      status: json['status']?.toString() ?? 'active',
      managerName:
          manager is Map<String, dynamic> ? manager['name']?.toString() : null,
      vehiclesCount: (json['vehicles_count'] as num?)?.toInt() ?? 0,
      // The API serializes decimals as strings ("9.03200000").
      latitude: _parseDouble(json['latitude']),
      longitude: _parseDouble(json['longitude']),
      openingTime: _parseTime(json['opening_time']),
      closingTime: _parseTime(json['closing_time']),
    );
  }

  static double? _parseDouble(dynamic value) {
    if (value == null) return null;
    return double.tryParse(value.toString());
  }

  /// Extracts the time part from an ISO datetime ("…T06:00:00.000Z")
  /// or passes through plain "HH:mm" strings.
  static String? _parseTime(dynamic value) {
    if (value == null) return null;
    final s = value.toString();
    if (s.length < 16 || !s.contains('T')) {
      return s.length >= 5 ? s.substring(0, 5) : null;
    }
    return s.substring(11, 16);
  }
}
