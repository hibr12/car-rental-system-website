/// Backend user roles (`users.role` column, `User::ROLES`).
enum UserRole {
  customer,
  superAdmin,
  admin,
  branchManager,
  fleetManager,
  staff,
  unknown;

  static UserRole parse(String? role) {
    switch (role?.toLowerCase()) {
      case 'customer':
        return UserRole.customer;
      case 'super_admin':
        return UserRole.superAdmin;
      case 'admin':
        return UserRole.admin;
      case 'branch_manager':
        return UserRole.branchManager;
      case 'fleet_manager':
        return UserRole.fleetManager;
      case 'staff':
        return UserRole.staff;
      default:
        return UserRole.unknown;
    }
  }

  String get label {
    switch (this) {
      case UserRole.customer:
        return 'Customer';
      case UserRole.superAdmin:
      case UserRole.admin:
        return 'Administrator';
      case UserRole.branchManager:
        return 'Branch Manager';
      case UserRole.fleetManager:
        return 'Fleet Manager';
      case UserRole.staff:
        return 'Staff';
      case UserRole.unknown:
        return 'Member';
    }
  }

  /// Non-customer roles see the staff/management system — the mobile app is
  /// customer-only and blocks them from self-service booking features.
  bool get isStaffRole => this != UserRole.customer;
}

class User {
  final String id;
  final String fullName;
  final String email;
  final String phone;
  final String profileImageUrl;

  /// True only when `email_verified_at` is set on the backend.
  final bool isVerified;
  final DateTime memberSince;
  final UserRole role;

  const User({
    required this.id,
    required this.fullName,
    required this.email,
    required this.phone,
    required this.profileImageUrl,
    this.isVerified = false,
    required this.memberSince,
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
      role: UserRole.customer,
    );
  }

  /// Parse a User from Laravel's UserResource JSON.
  ///
  /// Keys: id, name, email, phone, profile_photo, role, branch_id,
  /// branch?, email_verified_at, created_at, updated_at.
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'].toString(),
      fullName: (json['name'] as String?)?.trim() ?? '',
      email: json['email'] as String? ?? '',
      phone: json['phone'] as String? ?? '',
      profileImageUrl: _parsePhoto(json['profile_photo']),
      isVerified: json['email_verified_at'] != null,
      memberSince: _parseDate(json['created_at']),
      role: UserRole.parse(json['role'] as String?),
    );
  }

  static String _parsePhoto(dynamic value) {
    if (value is String) return value;
    return '';
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
