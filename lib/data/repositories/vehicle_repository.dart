import '../../core/config/api_endpoints.dart';
import '../../core/config/app_config.dart';
import '../../models/category_model.dart';
import '../../models/vehicle_model.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

/// Sorting options supported by the backend `VehicleController::index`
/// (`?sort=price_asc|price_desc|newest|oldest|year_desc|year_asc`).
enum VehicleSort {
  newest('Newest'),
  oldest('Oldest'),
  priceAsc('Price: Low to High'),
  priceDesc('Price: High to Low'),
  yearDesc('Year: Newest'),
  yearAsc('Year: Oldest');

  final String label;
  const VehicleSort(this.label);

  /// Value sent to the backend.
  String get value {
    switch (this) {
      case VehicleSort.newest:
        return 'newest';
      case VehicleSort.oldest:
        return 'oldest';
      case VehicleSort.priceAsc:
        return 'price_asc';
      case VehicleSort.priceDesc:
        return 'price_desc';
      case VehicleSort.yearDesc:
        return 'year_desc';
      case VehicleSort.yearAsc:
        return 'year_asc';
    }
  }
}

class VehicleRepository {
  VehicleRepository._internal();
  static final VehicleRepository instance = VehicleRepository._internal();

  final ApiClient _api = ApiClient.instance;

  // ── Featured / popular shortcuts (home screen) ─────────────────────

  Future<ApiResponse<List<Vehicle>>> getFeaturedVehicles() async {
    try {
      final json = await _api.get(
        ApiEndpoints.vehicles,
        queryParams: {
          'featured': 'true',
          'per_page': '5',
        },
      );
      final dataList = json['data'] as List? ?? [];
      final vehicles = dataList
          .map((item) => Vehicle.fromJson(item as Map<String, dynamic>))
          .toList();
      return ApiResponse.success(vehicles);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<List<Vehicle>>> getPopularVehicles() async {
    try {
      final json = await _api.get(
        ApiEndpoints.vehicles,
        queryParams: {
          'sort': VehicleSort.newest.value,
          'per_page': '10',
        },
      );
      final dataList = json['data'] as List? ?? [];
      final vehicles = dataList
          .map((item) => Vehicle.fromJson(item as Map<String, dynamic>))
          .toList();
      return ApiResponse.success(vehicles);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  // ── Categories ──────────────────────────────────────────────────────

  /// Fetch all vehicle categories from the API.
  ///
  /// Always returns at least an "All" entry so screens can render chips
  /// even when the endpoint is empty.
  Future<ApiResponse<List<VehicleCategory>>> getCategories() async {
    try {
      final json = await _api.get(ApiEndpoints.categories);
      final dataList = json['data'] as List? ?? [];
      final categories = dataList
          .map((item) => VehicleCategory.fromJson(item as Map<String, dynamic>))
          .toList();
      return ApiResponse.success(categories);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  // ── Paginated vehicle listing with full query support ──────────────

  /// Fetch a paginated list of vehicles using every query parameter the
  /// backend `VehicleController::index` supports.
  ///
  /// NOTE: `category` is matched against the category **slug**, not name.
  Future<ApiResponse<PaginatedResponse<Vehicle>>> getVehicles({
    int page = 1,
    int perPage = AppConfig.defaultPageSize,
    String? search,
    String? categorySlug,
    double? minPrice,
    double? maxPrice,
    String? fuelType,
    String? transmission,
    String? status,
    bool? featured,
    int? minSeats,
    String? branchId,
    VehicleSort sort = VehicleSort.newest,
    VehicleFilter filter = VehicleFilter.empty,
  }) async {
    try {
      // Merge the convenience args with the VehicleFilter bundle.
      final resolvedTransmission =
          (transmission ?? filter.transmission)?.toLowerCase();
      final resolvedMinPrice = minPrice ?? filter.minPrice;
      final resolvedMaxPrice = maxPrice ?? filter.maxPrice;

      final queryParams = <String, String>{
        'page': page.toString(),
        'per_page': perPage.toString(),
        'sort': sort.value,
      };
      if (search != null && search.trim().isNotEmpty) {
        queryParams['search'] = search.trim();
      }
      if (categorySlug != null && categorySlug.isNotEmpty) {
        queryParams['category'] = categorySlug;
      }
      if (resolvedMinPrice != null) {
        queryParams['min_price'] = resolvedMinPrice.toStringAsFixed(0);
      }
      if (resolvedMaxPrice != null) {
        queryParams['max_price'] = resolvedMaxPrice.toStringAsFixed(0);
      }
      if (fuelType != null && fuelType.isNotEmpty) {
        queryParams['fuel_type'] = fuelType;
      }
      // The backend matches transmission exactly ("automatic" / "manual"),
      // so "Any" must not be forwarded.
      if (resolvedTransmission != null &&
          resolvedTransmission.isNotEmpty &&
          resolvedTransmission != 'any') {
        queryParams['transmission'] = resolvedTransmission;
      }
      final resolvedMinSeats = minSeats ?? filter.minSeats;
      if (resolvedMinSeats != null && resolvedMinSeats > 0) {
        queryParams['min_seats'] = resolvedMinSeats.toString();
      }
      if (status != null && status.isNotEmpty) {
        queryParams['status'] = status;
      }
      if (featured != null) {
        queryParams['featured'] = featured ? 'true' : 'false';
      }
      // Backend `VehicleController::index` accepts a numeric branch filter.
      if (branchId != null && branchId.isNotEmpty && branchId != '0') {
        queryParams['branch_id'] = branchId;
      }

      final json =
          await _api.get(ApiEndpoints.vehicles, queryParams: queryParams);
      final paginated = PaginatedResponse<Vehicle>.fromJson(
        json,
        (item) => Vehicle.fromJson(item),
      );
      return ApiResponse.success(paginated);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  // ── Single vehicle ──────────────────────────────────────────────────

  Future<ApiResponse<Vehicle>> getVehicleById(String id) async {
    try {
      final json = await _api.get(ApiEndpoints.vehicle(int.tryParse(id) ?? 0));
      final vehicleData = json['data'] as Map<String, dynamic>;
      final vehicle = Vehicle.fromJson(vehicleData);
      return ApiResponse.success(vehicle);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
