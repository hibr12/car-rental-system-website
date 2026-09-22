/// Effective license status from `DriverLicenseResource::effectiveStatus()`.
///
/// One of `pending_review`, `verified`, `rejected`, `expired` (a verified or
/// pending license past its `expiry_date` is upgraded to `expired` at read
/// time). The raw DB status may additionally be `replaced`.
enum LicenseStatus {
  pendingReview,
  verified,
  rejected,
  expired;

  static LicenseStatus fromString(String? status) {
    switch (status?.toLowerCase()) {
      case 'verified':
        return LicenseStatus.verified;
      case 'rejected':
        return LicenseStatus.rejected;
      case 'expired':
        return LicenseStatus.expired;
      case 'pending_review':
      case 'pending':
      default:
        return LicenseStatus.pendingReview;
    }
  }

  String get label {
    switch (this) {
      case LicenseStatus.pendingReview:
        return 'Pending Review';
      case LicenseStatus.verified:
        return 'Verified';
      case LicenseStatus.rejected:
        return 'Rejected';
      case LicenseStatus.expired:
        return 'Expired';
    }
  }
}

class DriverLicense {
  final int id;
  final LicenseStatus status;

  /// Masked for customers by the backend, e.g. "••••••••4821".
  final String? licenseNumber;

  /// Always-masked number from `license_number_masked`.
  final String? licenseNumberMasked;
  final String fullName;
  final String? dateOfBirth;
  final String licenseCategory;
  final String issueDate;
  final String expiryDate;
  final String? issuingAuthority;
  final String? issuingCountry;
  final bool hasFrontDocument;
  final bool hasBackDocument;

  /// Absolute URLs to the AUTHENTICATED document endpoints
  /// (`/api/licenses/{id}/document/{front|back}`). They require the
  /// Authorization header — plain image widgets cannot load them.
  final String? frontDocumentUrl;
  final String? backDocumentUrl;
  final String? rejectionReason;
  final int? daysUntilExpiry;
  final DateTime? submittedAt;
  final DateTime? verifiedAt;
  final DateTime createdAt;
  final DateTime updatedAt;

  DriverLicense({
    required this.id,
    required this.status,
    this.licenseNumber,
    this.licenseNumberMasked,
    required this.fullName,
    this.dateOfBirth,
    required this.licenseCategory,
    required this.issueDate,
    required this.expiryDate,
    this.issuingAuthority,
    this.issuingCountry,
    required this.hasFrontDocument,
    required this.hasBackDocument,
    this.frontDocumentUrl,
    this.backDocumentUrl,
    this.rejectionReason,
    this.daysUntilExpiry,
    this.submittedAt,
    this.verifiedAt,
    required this.createdAt,
    required this.updatedAt,
  });

  factory DriverLicense.fromJson(Map<String, dynamic> json) {
    DateTime? tryParse(dynamic v) =>
        v is String && v.isNotEmpty ? DateTime.tryParse(v) : null;

    return DriverLicense(
      id: (json['id'] as num?)?.toInt() ?? 0,
      status: LicenseStatus.fromString(json['status'] as String?),
      licenseNumber: json['license_number'] as String?,
      licenseNumberMasked: json['license_number_masked'] as String?,
      fullName: json['full_name'] as String? ?? '',
      dateOfBirth: json['date_of_birth'] as String?,
      licenseCategory: json['license_category'] as String? ?? '',
      issueDate: json['issue_date'] as String? ?? '',
      expiryDate: json['expiry_date'] as String? ?? '',
      issuingAuthority: json['issuing_authority'] as String?,
      issuingCountry: json['issuing_country'] as String?,
      hasFrontDocument: json['has_front_document'] as bool? ?? false,
      hasBackDocument: json['has_back_document'] as bool? ?? false,
      frontDocumentUrl: json['front_document_url'] as String?,
      backDocumentUrl: json['back_document_url'] as String?,
      rejectionReason: json['rejection_reason'] as String?,
      daysUntilExpiry: (json['days_until_expiry'] as num?)?.toInt(),
      submittedAt: tryParse(json['submitted_at']),
      verifiedAt: tryParse(json['verified_at']),
      createdAt: tryParse(json['created_at']) ?? DateTime.now(),
      updatedAt: tryParse(json['updated_at']) ?? DateTime.now(),
    );
  }
}
