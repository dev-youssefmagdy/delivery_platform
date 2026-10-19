import 'api_exception.dart';

/// A successful response with the envelope already unwrapped.
class ApiResult<T> {
  const ApiResult({required this.data, this.message, this.meta = const {}});

  final T data;
  final String? message;
  final Map<String, dynamic> meta;
}

/// Parses the backend envelope `{success, message, data, meta, errors}`.
abstract final class EnvelopeParser {
  static const _codeKey = '_code';

  /// Returns the decoded envelope map or null when [body] is not an envelope.
  static Map<String, dynamic>? asEnvelope(Object? body) {
    if (body is Map && body.containsKey('success')) {
      return Map<String, dynamic>.from(body);
    }
    return null;
  }

  static ApiResult<Object?> parseSuccess(Object? body) {
    final env = asEnvelope(body);
    if (env == null) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    final meta = env['meta'];
    return ApiResult<Object?>(
      data: env['data'],
      message: env['message'] as String?,
      meta: meta is Map ? Map<String, dynamic>.from(meta) : const {},
    );
  }

  static ApiException parseError({
    required int? statusCode,
    required Object? body,
    String? requestIdHeader,
    String? retryAfterHeader,
  }) {
    final env = asEnvelope(body);
    var code = 'http_${statusCode ?? 0}';
    String? message;
    final fields = <String, List<String>>{};
    String? requestId = requestIdHeader;
    int? retryAfter = int.tryParse(retryAfterHeader ?? '');

    if (env != null) {
      message = env['message'] as String?;
      final errors = env['errors'];
      if (errors is Map) {
        errors.forEach((key, value) {
          if (key == _codeKey) {
            if (value is String && value.isNotEmpty) code = value;
          } else if (value is List) {
            fields['$key'] = value.map((e) => '$e').toList(growable: false);
          } else if (value is String) {
            fields['$key'] = [value];
          }
        });
      }
      final meta = env['meta'];
      if (meta is Map) {
        final rid = meta['request_id'];
        if (rid is String && rid.isNotEmpty) requestId = rid;
        final ra = meta['retry_after'];
        if (ra is int) retryAfter = ra;
      }
    }

    return ApiException(
      kind: kindForStatus(statusCode),
      code: code,
      message: message,
      statusCode: statusCode,
      fieldErrors: fields,
      requestId: requestId,
      retryAfterSeconds: retryAfter,
    );
  }

  static ApiFailureKind kindForStatus(int? status) {
    switch (status) {
      case 401:
      case 419:
        return ApiFailureKind.unauthenticated;
      case 403:
        return ApiFailureKind.forbidden;
      case 404:
        return ApiFailureKind.notFound;
      case 409:
        return ApiFailureKind.conflict;
      case 422:
        return ApiFailureKind.validation;
      case 429:
        return ApiFailureKind.rateLimited;
      case final s? when s >= 500:
        return ApiFailureKind.server;
      default:
        return ApiFailureKind.unknown;
    }
  }
}
