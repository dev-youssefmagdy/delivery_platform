import 'dart:async';
import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../config/env.dart';
import 'api_exception.dart';
import 'api_result.dart';
import 'interceptors.dart';

/// The only place that talks HTTP. Widgets never touch dio (Plan §6.2);
/// repositories/datasources call this and receive unwrapped [ApiResult]s or
/// an [ApiException].
class ApiClient {
  ApiClient({
    required Env env,
    required FutureOr<String?> Function() readToken,
    required String Function() readLanguageCode,
    Dio? dio,
    int maxRetries = 2,
    Future<void> Function(Duration)? retrySleep,
  }) : _dio =
           dio ??
           Dio(
             BaseOptions(
               baseUrl: env.apiBaseUrl,
               connectTimeout: const Duration(seconds: 15),
               receiveTimeout: const Duration(seconds: 30),
               sendTimeout: const Duration(seconds: 30),
               contentType: Headers.jsonContentType,
               headers: {Headers.acceptHeader: Headers.jsonContentType},
               validateStatus: (status) => status != null && status < 400,
             ),
           ) {
    _dio.interceptors.addAll([
      RequestIdInterceptor(),
      LocaleInterceptor(readLanguageCode),
      AuthInterceptor(
        readToken: readToken,
        onSessionExpired: () {
          if (!_sessionExpired.isClosed) _sessionExpired.add(null);
        },
      ),
      RetryInterceptor(dio: _dio, maxRetries: maxRetries, sleep: retrySleep),
    ]);
  }

  final Dio _dio;
  final _sessionExpired = StreamController<void>.broadcast();

  /// Emits whenever an authenticated request is rejected with 401.
  Stream<void> get sessionExpired => _sessionExpired.stream;

  Future<ApiResult<Object?>> get(
    String path, {
    Map<String, dynamic>? query,
    CancelToken? cancelToken,
  }) => _send('GET', path, query: query, cancelToken: cancelToken);

  /// [idempotencyKey]: pass the key generated once per user intent for
  /// critical writes, and reuse it when retrying that intent.
  Future<ApiResult<Object?>> post(
    String path, {
    Object? body,
    Map<String, dynamic>? query,
    String? idempotencyKey,
    Map<String, String>? headers,
    CancelToken? cancelToken,
  }) => _send(
    'POST',
    path,
    body: body,
    query: query,
    idempotencyKey: idempotencyKey,
    headers: headers,
    cancelToken: cancelToken,
  );

  Future<ApiResult<Object?>> put(
    String path, {
    Object? body,
    String? idempotencyKey,
  }) => _send('PUT', path, body: body, idempotencyKey: idempotencyKey);

  Future<ApiResult<Object?>> patch(
    String path, {
    Object? body,
    String? idempotencyKey,
  }) => _send('PATCH', path, body: body, idempotencyKey: idempotencyKey);

  Future<ApiResult<Object?>> delete(
    String path, {
    Object? body,
    String? idempotencyKey,
  }) => _send('DELETE', path, body: body, idempotencyKey: idempotencyKey);

  Future<ApiResult<Object?>> _send(
    String method,
    String path, {
    Object? body,
    Map<String, dynamic>? query,
    String? idempotencyKey,
    Map<String, String>? headers,
    CancelToken? cancelToken,
  }) async {
    try {
      final response = await _dio.request<Object?>(
        path,
        data: body,
        queryParameters: query,
        cancelToken: cancelToken,
        options: Options(
          method: method,
          headers: {ApiHeaders.idempotencyKey: ?idempotencyKey, ...?headers},
        ),
      );
      return EnvelopeParser.parseSuccess(response.data);
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  /// Downloads a private file (a signed attachment URL) with the same auth
  /// and error handling as every other call. Used for chat and ticket images.
  Future<Uint8List> getBytes(String url) async {
    try {
      final response = await _dio.get<List<int>>(
        url,
        options: Options(responseType: ResponseType.bytes),
      );
      return Uint8List.fromList(response.data ?? const <int>[]);
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  ApiException _toApiException(DioException e) {
    final response = e.response;
    if (response != null) {
      return EnvelopeParser.parseError(
        statusCode: response.statusCode,
        body: response.data,
        requestIdHeader: response.headers.value(ApiHeaders.requestId),
        retryAfterHeader: response.headers.value('retry-after'),
      );
    }
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return const ApiException(
          kind: ApiFailureKind.timeout,
          code: 'timeout',
          message: null,
        );
      case DioExceptionType.cancel:
        return const ApiException(
          kind: ApiFailureKind.cancelled,
          code: 'cancelled',
          message: null,
        );
      case DioExceptionType.connectionError:
        return const ApiException(
          kind: ApiFailureKind.network,
          code: 'network_error',
          message: null,
        );
      default:
        return const ApiException(
          kind: ApiFailureKind.unknown,
          code: 'unknown_error',
          message: null,
        );
    }
  }

  void dispose() {
    _sessionExpired.close();
    _dio.close();
  }
}
