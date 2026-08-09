import 'owner_model.dart';

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
  final List<String> features;
  final String category; // e.g., 'Luxury', 'SUV', 'Electric', 'Economy'
  final bool isFeatured;
  final Owner owner;

  const Vehicle({
    required this.id,
    required this.brand,
    required this.model,
    required this.year,
    required this.pricePerDay,
    required this.rating,
    required this.reviewCount,
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
    required this.features,
    required this.category,
    this.isFeatured = false,
    required this.owner,
  });

  String get fullName => '$brand $model $year';

  /// Parse a Vehicle from Laravel's VehicleResource JSON.
  ///
  /// The response includes:
  /// - `category` → nested CategoryResource (`name`, `slug`, `vehicles_count`)
  /// - `images` → list of VehicleImageResource (`image_url`, `is_primary`)
  /// - `primary_image` → single VehicleImageResource (optional)
  factory Vehicle.fromJson(Map<String, dynamic> json) {
    // ── Images ──────────────────────────────────────────────────────
    List<String> imageUrls = [];
    if (json['images'] != null && json['images'] is List) {
      imageUrls = (json['images'] as List)
          .map((img) => img['image_url'] as String? ?? '')
          .where((url) => url.isNotEmpty)
          .toList();
    }
    // Fallback to primary_image if the images array was empty.
    if (imageUrls.isEmpty && json['primary_image'] != null) {
      final p = json['primary_image'] as Map<String, dynamic>?;
      final url = p?['image_url'] as String?;
      if (url != null && url.isNotEmpty) imageUrls = [url];
    }
    // Ultimate fallback — avoid `.first` crashes everywhere.
    if (imageUrls.isEmpty) imageUrls = [placeholderVehicleImage];

    // ── Category ────────────────────────────────────────────────────
    String categoryName = 'Other';
    if (json['category'] != null && json['category'] is Map) {
      categoryName = json['category']['name'] as String? ?? 'Other';
    }

    // ── Status ──────────────────────────────────────────────────────
    final rawStatus = json['status'] as String? ?? 'available';

    return Vehicle(
      id: json['id'].toString(),
      brand: json['brand'] as String? ?? '',
      model: json['model'] as String? ?? '',
      year: (json['year'] as num?)?.toInt() ?? 0,
      pricePerDay: _parseDouble(json['rental_price_per_day']),
      rating: _parseDouble(json['rating']),
      reviewCount: (json['review_count'] as num?)?.toInt() ?? 0,
      fuelType: json['fuel_type'] as String? ?? '',
      transmission: json['transmission'] as String? ?? '',
      seats: (json['seats'] as num?)?.toInt() ?? 4,
      color: json['color'] as String? ?? '',
      location: json['location'] as String? ?? '',
      status: rawStatus,
      isAvailable: rawStatus.toLowerCase() == 'available',
      description: json['description'] as String? ?? '',
      mileage: (json['mileage'] as num?)?.toInt() ?? 0,
      registrationNumber: json['registration_number'] as String? ?? '',
      imageUrls: imageUrls,
      features: [], // Laravel does not provide a features array.
      category: categoryName,
      isFeatured: json['featured'] == true,
      owner: _parseOwner(json['creator']),
    );
  }

  /// Try to parse the optional `creator` relation (admin/fleet_manager).
  /// Falls back to a placeholder for normal API consumers.
  static Owner _parseOwner(dynamic creatorJson) {
    if (creatorJson is Map<String, dynamic> && creatorJson.isNotEmpty) {
      return Owner.fromJson(creatorJson);
    }
    return Owner.placeholder();
  }

  static double _parseDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}
