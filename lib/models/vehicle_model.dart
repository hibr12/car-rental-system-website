/// Placeholder image used when a vehicle has no images at all.
/// Prevents `.first` crashes in VehicleCard, reservations, and details.
const placeholderVehicleImage =
    'https://placehold.co/800x600/e2e8f0/64748b?text=No+Image';

class Vehicle {
  final String id;
  final String brand;
  final String model;
  final int year;
  final double pricePerDay;

  /// Aggregates the backend does NOT serialize on vehicles
  /// (`VehicleResource` has no rating/review keys). Populated only when a
  /// caller explicitly enriches the vehicle from `/vehicles/{id}/reviews`
  /// (whose `meta` carries `average_rating`). Zero means "unknown".
  final double rating;
  final int reviewCount;

  final String fuelType;
  final String transmission;
  final int seats;
  final String color;
  final String location;
  final String status;
  final bool isAvailable;
  final String description;
  final int mileage;
  final String registrationNumber;
  final List<String> imageUrls;

  /// The backend provides no features array today — always empty. UI must
  /// hide feature sections instead of showing fabricated chips.
  final List<String> features;
  final String category;
  final bool isFeatured;
  final String branchId;
  final String branchName;

  const Vehicle({
    required this.id,
    required this.brand,
    required this.model,
    required this.year,
    required this.pricePerDay,
    this.rating = 0,
    this.reviewCount = 0,
    required this.fuelType,
    required this.transmission,
    required this.seats,
    required this.color,
    required this.location,
    this.status = 'available',
    this.isAvailable = true,
    required this.description,
    this.mileage = 0,
    this.registrationNumber = '',
    required this.imageUrls,
    this.features = const [],
    required this.category,
    this.isFeatured = false,
    this.branchId = '',
    this.branchName = '',
  });

  String get fullName => '$brand $model $year';

  /// Whether trustworthy rating data exists (backend never sends it on the
  /// vehicle resource itself, so this is only true after explicit enrichment).
  bool get hasRating => reviewCount > 0 && rating > 0;

  /// Parse a Vehicle from Laravel's VehicleResource JSON.
  ///
  /// Response keys: id, brand, model, year, registration_number, description,
  /// fuel_type, transmission, seats, color, mileage, rental_price_per_day
  /// (decimal → string), status, featured, location, branch_id, branch
  /// (when loaded: id/name/code/city/status), category, images,
  /// primary_image, created_at, updated_at.
  factory Vehicle.fromJson(Map<String, dynamic> json) {
    // ── Images ──────────────────────────────────────────────────────
    List<String> imageUrls = [];
    if (json['images'] != null && json['images'] is List) {
      imageUrls = (json['images'] as List)
          .whereType<Map<String, dynamic>>()
          .map((img) => img['image_url'] as String? ?? '')
          .where((url) => url.isNotEmpty)
          .toList();
    }
    // Fallback to primary_image if the images array was empty.
    if (imageUrls.isEmpty && json['primary_image'] is Map<String, dynamic>) {
      final url =
          (json['primary_image'] as Map<String, dynamic>)['image_url'] as String?;
      if (url != null && url.isNotEmpty) imageUrls = [url];
    }
    // Ultimate fallback — avoid `.first` crashes everywhere.
    if (imageUrls.isEmpty) imageUrls = [placeholderVehicleImage];

    // ── Category ────────────────────────────────────────────────────
    String categoryName = 'Other';
    if (json['category'] is Map<String, dynamic>) {
      categoryName = (json['category'] as Map<String, dynamic>)['name'] as String? ?? 'Other';
    }

    // ── Branch ──────────────────────────────────────────────────────
    final branch = json['branch'];
    final branchMap = branch is Map<String, dynamic> ? branch : <String, dynamic>{};

    // ── Status ──────────────────────────────────────────────────────
    final rawStatus = json['status'] as String? ?? 'available';

    return Vehicle(
      id: json['id'].toString(),
      brand: json['brand'] as String? ?? '',
      model: json['model'] as String? ?? '',
      year: (json['year'] as num?)?.toInt() ?? 0,
      pricePerDay: _parseDouble(json['rental_price_per_day']),
      fuelType: json['fuel_type'] as String? ?? '',
      transmission: json['transmission'] as String? ?? '',
      seats: (json['seats'] as num?)?.toInt() ?? 4,
      color: json['color'] as String? ?? '',
      location: json['location'] as String? ?? '',
      status: rawStatus,
      isAvailable: rawStatus.toLowerCase() == 'available',
      description: json['description'] as String? ?? '',
      mileage: _parseInt(json['mileage']),
      registrationNumber: json['registration_number'] as String? ?? '',
      imageUrls: imageUrls,
      category: categoryName,
      isFeatured: json['featured'] == true || json['featured'] == 1,
      branchId: (json['branch_id'] ?? branchMap['id'])?.toString() ?? '',
      branchName: branchMap['name'] as String? ?? '',
    );
  }

  /// Returns a copy of this vehicle with enriched rating aggregates.
  Vehicle withRating(double averageRating, int reviewCount) {
    return Vehicle(
      id: id,
      brand: brand,
      model: model,
      year: year,
      pricePerDay: pricePerDay,
      rating: averageRating,
      reviewCount: reviewCount,
      fuelType: fuelType,
      transmission: transmission,
      seats: seats,
      color: color,
      location: location,
      status: status,
      isAvailable: isAvailable,
      description: description,
      mileage: mileage,
      registrationNumber: registrationNumber,
      imageUrls: imageUrls,
      features: features,
      category: category,
      isFeatured: isFeatured,
      branchId: branchId,
      branchName: branchName,
    );
  }

  static int _parseInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value) ?? 0;
    return 0;
  }

  static double _parseDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value.replaceAll(',', '').trim()) ?? 0.0;
    return 0.0;
  }
}
