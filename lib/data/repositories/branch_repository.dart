import '../../core/config/api_endpoints.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

/// Repository for public branch data (`GET /branches`, `GET /branches/{id}`).
class BranchRepository {
  static final BranchRepository instance = BranchRepository._internal();
  BranchRepository._internal();

  final ApiClient _api = ApiClient.instance;

  /// Guests/customers receive active branches only.
  Future<ApiResponse<List<Map<String, dynamic>>>> getBranches() async {
    try {
      final json = await _api.get(ApiEndpoints.branches);
      final dataList = json['data'] as List? ?? [];
      return ApiResponse.success(
          dataList.map((e) => e as Map<String, dynamic>).toList());
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getBranchById(String id) async {
    try {
      final json =
          await _api.get(ApiEndpoints.branch(int.tryParse(id) ?? 0));
      return ApiResponse.success(json['data'] as Map<String, dynamic>);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
