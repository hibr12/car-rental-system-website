import '../../core/config/api_endpoints.dart';
import '../../models/notification_model.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

class NotificationRepository {
  static final NotificationRepository instance = NotificationRepository._internal();
  NotificationRepository._internal();

  final ApiClient _api = ApiClient.instance;

  Future<ApiResponse<PaginatedResponse<AppNotification>>> getNotifications({int page = 1}) async {
    try {
      final queryParams = {'page': page.toString()};
      final json = await _api.get(ApiEndpoints.notifications, queryParams: queryParams);
      
      final paginated = PaginatedResponse<AppNotification>.fromJson(
        json,
        (item) => AppNotification.fromJson(item),
      );
      
      return ApiResponse.success(paginated);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<bool>> markAsRead(String id) async {
    try {
      await _api.put(ApiEndpoints.notificationRead(id));
      return ApiResponse.success(true);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<bool>> markAllAsRead() async {
    try {
      await _api.put(ApiEndpoints.notificationsReadAll);
      return ApiResponse.success(true);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<bool>> deleteNotification(String id) async {
    try {
      await _api.delete(ApiEndpoints.notification(id));
      return ApiResponse.success(true);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
