/// A company branch parsed from Laravel's `BranchResource`.
///
/// Serialized fields: `id`, `name`, `address`, `city`, `phone`, `email`,
/// `status` (`active` | `inactive`), optional `manager`, and `*_count`
/// values when counted. The branches table no longer carries coordinates
/// or opening hours (they were dropped by migration), so none are exposed.
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
  });

  bool get isActive => status.toLowerCase() == 'active';

  /// Single-line location, e.g. "Bole Road, Addis Ababa".
  String get locationLine {
    final parts = [address.trim(), city.trim()]
        .where((p) => p.isNotEmpty)
        .toList(growable: false);
    return parts.join(', ');
  }

  factory Branch.fromJson(Map<String, dynamic> json) {
    final manager = json['manager'];
    return Branch(
      id: json['id'] is int ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      status: json['status']?.toString() ?? 'active',
      managerName:
          manager is Map<String, dynamic> ? manager['name']?.toString() : null,
      vehiclesCount: (json['vehicles_count'] as num?)?.toInt() ?? 0,
    );
  }
}
