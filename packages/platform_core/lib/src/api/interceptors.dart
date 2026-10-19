import 'dart:async';
import 'dart:math';

import 'package:dio/dio.dart';
import 'package:uuid/uuid.dart';

const _uuid = Uuid();

/// Generates the client-side `Idempotency-Key` (UUID v4). Create one per user
/// intent and reuse it across retries of that intent (ADR-28, Plan §6.2).
String newIdempotencyKey() => _uuid.v4();

abstract final class ApiHeaders {
  static const idempotencyKey = 'Idempotency-Key';
  static const requestId = 'X-Request-Id';
  static const acceptLanguage = 'Accept-Language';
  static const authorization = 'Authorization';
}

/// Extra-map flag set when the request carried a bearer token, so a 401 can be
/// told apart from a failed login attempt.
const _hadTokenKey = 'platform_core.had_token';

class AuthInterceptor extends Interceptor {
  AuthInterceptor({required this.readToken, required this.onSessionExpired});

  final FutureOr<String?> Function() readToken;
  final void Function() onSessionExpired;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await readToken();
    if (token != null && token.isNotEmpty) {
      options.headers[ApiHeaders.authorization] = 'Bearer $token';
      options.extra[_hadTokenKey] = true;
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401 &&
        err.requestOptions.extra[_hadTokenKey] == true) {
      onSessionExpired();
    }
    handler.next(err);
  }
}

class LocaleInterceptor extends Interceptor {
  LocaleInterceptor(this.readLanguageCode);

  final String Function() readLanguageCode;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    options.headers[ApiHeaders.acceptLanguage] = readLanguageCode();
    handler.next(options);
  }
}

class RequestIdInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    options.headers.putIfAbsent(ApiHeaders.requestId, _uuid.v4);
    handler.next(options);
  }
}

/// Retries idempotent GET requests on transport errors and 502/503/504 with
/// exponential backoff + jitter. Never retries unsafe methods.
class RetryInterceptor extends Interceptor {
  RetryInterceptor({
    required this.dio,
    this.maxRetries = 2,
    this.baseDelay = const Duration(milliseconds: 400),
    Future<void> Function(Duration)? sleep,
  }) : _sleep = sleep ?? Future<void>.delayed;

  final Dio dio;
  final int maxRetries;
  final Duration baseDelay;
  final Future<void> Function(Duration) _sleep;
  final _random = Random();

  static const _attemptKey = 'platform_core.retry_attempt';

  bool _retryable(DioException e) {
    if (e.requestOptions.method.toUpperCase() != 'GET') return false;
    if (e.type == DioExceptionType.cancel) return false;
    final status = e.response?.statusCode;
    if (status != null) return status == 502 || status == 503 || status == 504;
    return e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout;
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    final attempt = (err.requestOptions.extra[_attemptKey] as int?) ?? 0;
    if (!_retryable(err) || attempt >= maxRetries) {
      return handler.next(err);
    }
    final delay =
        baseDelay * pow(2, attempt).toInt() +
        Duration(milliseconds: _random.nextInt(120));
    await _sleep(delay);
    final options = err.requestOptions..extra[_attemptKey] = attempt + 1;
    try {
      handler.resolve(await dio.fetch<dynamic>(options));
    } on DioException catch (e) {
      handler.next(e);
    }
  }
}
