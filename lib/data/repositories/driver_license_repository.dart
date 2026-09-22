import 'dart:io';

import '../../core/config/api_endpoints.dart';
import '../../models/driver_license_model.dart';
import '../api/api_client.dart';
import '../models/api_models.dart';

class DriverLicenseRepository {
  static final DriverLicenseRepository instance = DriverLicenseRepository._internal();
  DriverLicenseRepository._internal();

  final ApiClient _api = ApiClient.instance;

  Future<ApiResponse<DriverLicense?>> getMyLicense() async {
    try {
      final json = await _api.get(ApiEndpoints.customerLicense);
      if (json['data'] == null) {
        return ApiResponse.success(null);
      }
      final data = json['data'] as Map<String, dynamic>;
      final license = DriverLicense.fromJson(data);
      return ApiResponse.success(license);
    } on ApiException catch (e) {
      if (e.error.statusCode == 404) {
        return ApiResponse.success(null);
      }
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<DriverLicense>> submitLicense({
    required Map<String, String> fields,
    required File frontDocument,
    required File backDocument,
  }) async {
    try {
      final json = await _api.multipart(
        ApiEndpoints.customerLicense,
        fields: fields,
        files: {
          'front_document': frontDocument,
          'back_document': backDocument,
        },
      );
      final data = json['data'] as Map<String, dynamic>;
      final license = DriverLicense.fromJson(data);
      return ApiResponse.success(license);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<DriverLicense>> updateDocuments({
    File? frontDocument,
    File? backDocument,
  }) async {
    try {
      final files = <String, File>{};
      if (frontDocument != null) files['front_document'] = frontDocument;
      if (backDocument != null) files['back_document'] = backDocument;

      final json = await _api.multipart(
        ApiEndpoints.customerLicenseDocuments,
        files: files,
      );
      final data = json['data'] as Map<String, dynamic>;
      final license = DriverLicense.fromJson(data);
      return ApiResponse.success(license);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> checkEligibility({int? vehicleId}) async {
    try {
      final queryParams = vehicleId != null ? {'vehicle_id': vehicleId.toString()} : null;
      final json = await _api.get(ApiEndpoints.customerLicenseEligibility, queryParams: queryParams);
      return ApiResponse.success(json);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
