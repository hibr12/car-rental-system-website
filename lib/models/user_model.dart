import 'dart:convert';

/// Backend user roles (from `users.role` column): customer, admin, fleet_manager.
enum UserRole {
  customer,
  admin,
  fleetManager,
  unknown;

  static UserRole parse(String? role) {
    switch (role?.toLowerCase()) {
      case 'customer':
        return UserRole.customer;
      case 'admin':
        return UserRole.admin;
      case 'fleet_manager':
      case 'fleetmanager':
        return UserRole.fleetManager;
      default:
        return UserRole.unknown;
    }
  }

  String get label {
    switch (this) {
      case UserRole.customer:
        return 'Customer';
      case UserRole.admin:
        return 'Administrator';
      case UserRole.fleetManager:
        return 'Fleet Manager';
      case UserRole.unknown:
        return 'Member';
    }
  }

  bool get isAdmin => this == UserRole.admin;
  bool get isFleetManager => this == UserRole.fleetManager;
  bool get isStaff => isAdmin || isFleetManager;
}

class User {
  final String id;
  final String fullName;
  final String email;
  final String phone;
  final String profileImageUrl;
  final bool isVerified;
  final DateTime memberSince;
  final int rewardPoints;
  final UserRole role;

  const User({
    required this.id,
    required this.fullName,
    required this.email,
    required this.phone,
    required this.profileImageUrl,
    this.isVerified = false,
    required this.memberSince,
    this.rewardPoints = 0,
    this.role = UserRole.customer,
  });

  /// Default placeholder used when no user is available.
  factory User.placeholder() {
    return User(
      id: '0',
      fullName: 'Guest User',
      email: '',
      phone: '',
      profileImageUrl: '',
      isVerified: false,
      memberSince: DateTime.now(),
      rewardPoints: 0,
      role: UserRole.customer,
    );
  }

  /// Parse a User from Laravel's UserResource JSON.
  ///
  /// UserResource fields: id, name, email, phone, profile_photo, role,
  /// email_verified_at, created_at, updated_at.
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'].toString(),
      fullName: (json['name'] as String?)?.trim() ?? '',
      email: json['email'] as String? ?? '',
      phone: json['phone'] as String? ?? '',
      profileImageUrl: _parsePhoto(json['profile_photo']),
      isVerified: json['email_verified_at'] != null,
      memberSince: _parseDate(json['created_at']),
      rewardPoints: 0, // Not provided by the API (documented as missing).
      role: UserRole.parse(json['role'] as String?),
    );
  }

  /// Some Laravel installations return `profile_photo` as a full URL, others
  /// as a relative path. Normalize whatever we get.
  static String _parsePhoto(dynamic value) {
    if (value == null) return '';
    if (value is String) return value;
    // Tolerate an unexpected object/encoded value without throwing.
    try {
      if (value is List) return '';
      return json.encode(value);
    } catch (_) {
      return '';
    }
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
}
