/// A vehicle category parsed from Laravel's CategoryResource.
///
/// CategoryResource fields: id, name, slug, description, vehicles_count,
/// created_at, updated_at.
class VehicleCategory {
  final int id;
  final String name;
  final String slug;
  final String description;
  final int vehiclesCount;

  const VehicleCategory({
    required this.id,
    required this.name,
    required this.slug,
    this.description = '',
    this.vehiclesCount = 0,
  });

  factory VehicleCategory.fromJson(Map<String, dynamic> json) {
    return VehicleCategory(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] as String?)?.trim() ?? 'Other',
      slug: (json['slug'] as String?)?.trim() ?? '',
      description: json['description'] as String? ?? '',
      vehiclesCount: (json['vehicles_count'] as num?)?.toInt() ?? 0,
    );
  }

  @override
  String toString() => 'VehicleCategory($name)';
}

/// A bundle of vehicle filters returned from the [FilterBottomSheet].
///
/// `null` values mean "no filter" on that dimension; non-null values are
/// forwarded to the `/vehicles` query string.
class VehicleFilter {
  final double? minPrice;
  final double? maxPrice;
  final String? transmission; // 'Any' | 'Automatic' | 'Manual'
  final int? minSeats;

  const VehicleFilter({
    this.minPrice,
    this.maxPrice,
    this.transmission,
    this.minSeats,
  });

  /// Sentinel meaning "no active filters".
  static const VehicleFilter empty = VehicleFilter();

  bool get isActive =>
      minPrice != null ||
      maxPrice != null ||
      (transmission != null &&
          transmission!.toLowerCase() != 'any' &&
          transmission!.isNotEmpty) ||
      minSeats != null;

  VehicleFilter copyWith({
    double? minPrice,
    double? maxPrice,
    String? transmission,
    int? minSeats,
  }) {
    return VehicleFilter(
      minPrice: minPrice ?? this.minPrice,
      maxPrice: maxPrice ?? this.maxPrice,
      transmission: transmission ?? this.transmission,
      minSeats: minSeats ?? this.minSeats,
    );
  }
}
