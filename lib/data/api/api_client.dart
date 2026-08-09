import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../core/config/app_config.dart';
import '../models/api_models.dart';
import 'token_storage.dart';

/// Centralized HTTP client for communicating with the Laravel API.
///
/// All requests go through [get], [post], [put], [delete] which:
///   1. Attach the Sanctum Bearer token (if available).
///   2. Set JSON content headers.
///   3. Enforce a configurable timeout.
///   4. Parse the response and translate errors into [ApiError].
///
/// On a **401** response the client invokes [onUnauthorized] so the
/// application can clear session state and redirect to login.
class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  final http.Client _httpClient = http.Client();

  /// Callback invoked when the server returns 401.
  /// Set this once in `main.dart` to wire up auth-state cleanup + redirect.
  void Function()? onUnauthorized;

  // ─── URL construction ─────────────────────────────────────────────

  Uri _uri(String path, {Map<String, String>? queryParams}) {
    final url = '${AppConfig.apiBaseUrl}$path';
    final uri = Uri.parse(url);
    return queryParams != null && queryParams.isNotEmpty
        ? uri.replace(queryParameters: queryParams)
        : uri;
  }

  // ─── Headers ──────────────────────────────────────────────────────

  Future<Map<String, String>> _headers() async {
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    final token = await TokenStorage.getToken();
    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }

    return headers;
  }

  // ─── HTTP methods ──────────────────────────────────────────────────

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, String>? queryParams,
  }) async {
    final uri = _uri(path, queryParams: queryParams);
    final headers = await _headers();

    final response = await _safeRequest(
      () => _httpClient.get(uri, headers: headers),
    );
    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final uri = _uri(path);
    final headers = await _headers();

    final response = await _safeRequest(
      () => _httpClient.post(
        uri,
        headers: headers,
        body: body != null ? jsonEncode(body) : null,
      ),
    );
    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> put(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final uri = _uri(path);
    final headers = await _headers();

    final response = await _safeRequest(
      () => _httpClient.put(
        uri,
        headers: headers,
        body: body != null ? jsonEncode(body) : null,
      ),
    );
    return _handleResponse(response);
  }

  Future<Map<String, dynamic>> delete(String path) async {
    final uri = _uri(path);
    final headers = await _headers();

    final response = await _safeRequest(
      () => _httpClient.delete(uri, headers: headers),
    );
    return _handleResponse(response);
  }

  /// Sends a multipart request (e.g. avatar upload when backend supports it).
  ///
  /// [fields] are string key-value pairs sent as text fields.
  /// [files] are `{ "field_name": File }` entries sent as file parts.
  Future<Map<String, dynamic>> multipart(
    String path, {
    Map<String, String>? fields,
    Map<String, File>? files,
  }) async {
    final uri = _uri(path);
    final token = await TokenStorage.getToken();
    final boundary =
        'driveease-boundary-${DateTime.now().millisecondsSinceEpoch}';

    final request = http.MultipartRequest('POST', uri)
      ..headers['Accept'] = 'application/json'
      ..headers['Authorization'] = 'Bearer ${token ?? ""}';

    fields?.forEach((key, value) => request.fields[key] = value);
    files?.forEach((key, file) {
      final length = file.lengthSync();
      final stream = file.openRead();
      request.files.add(http.MultipartFile(key, stream, length,
          filename: file.path.split(Platform.pathSeparator).last));
    });

    final streamed = await _safeRequestStreamed(() => request.send());
    final response = await http.Response.fromStream(streamed);
    return _handleResponse(response);
  }

  // ─── Error-safe request wrappers ──────────────────────────────────

  /// Wraps an [http.Client] call so that network-level failures are
  /// converted into [ApiException]s instead of unhandled [SocketException]s.
  Future<http.Response> _safeRequest(
      Future<http.Response> Function() fn) async {
    try {
      return await fn().timeout(AppConfig.timeoutDuration);
    } on TimeoutException {
      throw ApiException(const ApiError(
        statusCode: 0,
        message:
            'Request timed out. Please check your connection and try again.',
        isTimeout: true,
      ));
    } on SocketException {
      throw ApiException(const ApiError(
        statusCode: 0,
        message: 'No internet connection. Please check your network.',
        isNetworkError: true,
      ));
    } on HandshakeException {
      throw ApiException(const ApiError(
        statusCode: 0,
        message: 'Unable to connect to the server. Please try again later.',
        isNetworkError: true,
      ));
    } on http.ClientException {
      throw ApiException(const ApiError(
        statusCode: 0,
        message: 'Connection failed. Please check your network settings.',
        isNetworkError: true,
      ));
    }
  }

  Future<http.StreamedResponse> _safeRequestStreamed(
    Future<http.StreamedResponse> Function() fn,
  ) async {
    try {
      return await fn().timeout(AppConfig.timeoutDuration);
    } on TimeoutException {
      throw ApiException(const ApiError(
        statusCode: 0,
        message: 'Upload timed out.',
        isTimeout: true,
      ));
    } on SocketException {
      throw ApiException(const ApiError(
        statusCode: 0,
        message: 'No internet connection.',
        isNetworkError: true,
      ));
    }
  }

  // ─── Response handling ────────────────────────────────────────────

  Map<String, dynamic> _handleResponse(http.Response response) {
    // ── 401: session expired — notify app before throwing ──
    if (response.statusCode == 401) {
      onUnauthorized?.call();
    }

    // ── 2xx: decode and return ──
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return _decodeJson(response);
    }

    // ── Error: try to decode server JSON ──
    Map<String, dynamic> body;
    try {
      body = _decodeJson(response);
    } catch (_) {
      // Server may return HTML / plain-text on 5xx
      final status = response.statusCode;
      final hint = _statusHint(status);
      throw ApiException(ApiError(
        statusCode: status,
        message: hint,
      ));
    }

    // ── 422: Laravel validation errors ──
    final status = response.statusCode;
    ValidationError? validationError;
    if (status == 422 && body.containsKey('errors')) {
      final raw = body['errors'] as Map<String, dynamic>? ?? {};
      validationError = ValidationError(
        errors: raw.map(
          (k, v) => MapEntry(k, List<String>.from(v as List)),
        ),
      );
    }

    throw ApiException(ApiError(
      statusCode: response.statusCode,
      message: body['message'] as String? ?? _statusHint(response.statusCode),
      validationError: validationError,
    ));
  }

  Map<String, dynamic> _decodeJson(http.Response response) {
    try {
      return jsonDecode(response.body) as Map<String, dynamic>;
    } catch (_) {
      throw ApiException(const ApiError(
        statusCode: 0,
        message: 'Invalid response from server.',
      ));
    }
  }

  static String _statusHint(int status) {
    switch (status) {
      case 400:
        return 'Bad request. Please check your input.';
      case 401:
        return 'Session expired. Please log in again.';
      case 403:
        return 'You do not have permission to perform this action.';
      case 404:
        return 'The requested resource was not found.';
      case 422:
        return 'Validation failed.';
      case 429:
        return 'Too many requests. Please wait a moment and try again.';
      case 500:
        return 'Internal server error. Please try again later.';
      case 502:
      case 503:
        return 'The server is temporarily unavailable.';
      default:
        return 'Something went wrong. Please try again.';
    }
  }
}

/// Exception wrapper caught by repository methods.
class ApiException implements Exception {
  final ApiError error;
  const ApiException(this.error);

  @override
  String toString() => 'ApiException(${error.statusCode}: ${error.message})';
}
