/// Broad class of failure so UI can pick a state without parsing codes.
enum ApiFailureKind {
  network,
  timeout,
  unauthenticated,
  forbidden,
  notFound,
  conflict,
  validation,
  rateLimited,
  server,
  cancelled,
  unknown,
}

/// Error raised by `ApiClient` for any failed call (docs/API.md §2-3).
class ApiException implements Exception {
  const ApiException({
    required this.kind,
    required this.code,
    required this.message,
    this.statusCode,
    this.fieldErrors = const {},
    this.requestId,
    this.retryAfterSeconds,
  });

  final ApiFailureKind kind;

  /// Stable machine code (`errors._code`), e.g. `validation_failed`.
  final String code;

  /// Localized message from the server; null for transport errors, in which
  /// case the UI shows its own ARB text.
  final String? message;
  final int? statusCode;

  /// Field name to list of messages.
  final Map<String, List<String>> fieldErrors;
  final String? requestId;
  final int? retryAfterSeconds;

  bool get isSessionExpired => kind == ApiFailureKind.unauthenticated;
  bool get isOffline =>
      kind == ApiFailureKind.network || kind == ApiFailureKind.timeout;

  String? firstErrorFor(String field) {
    final list = fieldErrors[field];
    return (list == null || list.isEmpty) ? null : list.first;
  }

  @override
  String toString() =>
      'ApiException($code, status: $statusCode, requestId: $requestId)';
}
