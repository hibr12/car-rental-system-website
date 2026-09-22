/// Generic, backend-agnostic envelope for every repository response.
///
/// Repositories return [ApiResponse<T>] so screens can branch on
/// `success` and render appropriate loading/empty/error UI uniformly.
class ApiResponse<T> {
  final bool success;
  final String? message;
  final T? data;
  final ApiError? error;

  const ApiResponse({
    required this.success,
    this.message,
    this.data,
    this.error,
  });

  factory ApiResponse.success(T data, {String? message}) {
    return ApiResponse(success: true, message: message, data: data);
  }

  factory ApiResponse.error(ApiError error, {String? message}) {
    return ApiResponse(
      success: false,
      message: message ?? error.message,
      error: error,
    );
  }
}

/// Parses Laravel's custom paginated envelope.
///
/// The backend returns:
/// ```
/// { "success": true, "data": [...], "meta": { "current_page", "last_page",
/// "per_page", "total" } }
/// ```
/// Some responses (e.g. vehicle reviews) add extra keys to `meta`
/// (like `average_rating`) — those are read separately by callers.
class PaginatedResponse<T> {
  final List<T> data;
  final int currentPage;
  final int lastPage;
  final int total;
  final int perPage;

  /// Full `meta` map — some endpoints embed extra aggregates here
  /// (e.g. `average_rating` on vehicle reviews, `unread_count` on
  /// notifications).
  final Map<String, dynamic> meta;

  const PaginatedResponse({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.perPage,
    this.meta = const {},
  });

  bool get hasNextPage => currentPage < lastPage;

  factory PaginatedResponse.fromJson(
    Map<String, dynamic> jsonBody,
    T Function(Map<String, dynamic>) fromJson,
  ) {
    final dataList = jsonBody['data'] as List? ?? [];
    final meta =
        (jsonBody['meta'] as Map<String, dynamic>?) ?? <String, dynamic>{};

    return PaginatedResponse<T>(
      data: dataList
          .map((item) => fromJson(item as Map<String, dynamic>))
          .toList(),
      currentPage: (meta['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (meta['last_page'] as num?)?.toInt() ?? 1,
      total: (meta['total'] as num?)?.toInt() ?? 0,
      perPage: (meta['per_page'] as num?)?.toInt() ?? 10,
      meta: meta,
    );
  }

  /// Raw meta map accessor for responses that embed extra keys
  /// (e.g. `average_rating` on reviews).
  static Map<String, dynamic> metaOf(Map<String, dynamic> jsonBody) {
    return (jsonBody['meta'] as Map<String, dynamic>?) ?? <String, dynamic>{};
  }
}

/// Describes a failure returned (or inferred) by the API client.
class ApiError {
  final int statusCode;
  final String message;
  final ValidationError? validationError;

  /// True when the request never reached the server (offline / DNS / socket).
  final bool isNetworkError;

  /// True when the request exceeded the configured timeout.
  final bool isTimeout;

  const ApiError({
    required this.statusCode,
    required this.message,
    this.validationError,
    this.isNetworkError = false,
    this.isTimeout = false,
  });

  /// Whether this error means the current session is no longer valid.
  bool get isUnauthorized => statusCode == 401;

  /// First validation message for a given field, if any.
  String? errorFor(String field) => validationError?.getErrorFor(field);

  /// Human-readable, single-line summary suitable for a SnackBar/dialog.
  ///
  /// Prefers the server message; if there are validation errors, the first
  /// one is appended so the user always sees *what* to fix.
  String get friendlyMessage {
    final base = message.trim();
    final first = validationError?.firstMessage;
    if (first == null || first.isEmpty) return base;
    if (base.toLowerCase().contains(first.toLowerCase())) return base;
    return base.isEmpty ? first : '$base ($first)';
  }
}

/// Laravel 422 validation error payload: `{ "errors": { "field": ["..."] } }`.
class ValidationError {
  final Map<String, List<String>> errors;

  const ValidationError({required this.errors});

  String? getErrorFor(String field) {
    final list = errors[field];
    if (list != null && list.isNotEmpty) return list.first;
    return null;
  }

  /// The first message across all fields, or null if none.
  String? get firstMessage {
    for (final list in errors.values) {
      if (list.isNotEmpty) return list.first;
    }
    return null;
  }
}
