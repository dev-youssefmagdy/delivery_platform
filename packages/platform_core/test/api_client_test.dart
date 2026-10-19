import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

typedef Handler = ResponseBody Function(RequestOptions options);

class FakeAdapter implements HttpClientAdapter {
  FakeAdapter(this.handler);
  Handler handler;
  final requests = <RequestOptions>[];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    return handler(options);
  }

  @override
  void close({bool force = false}) {}
}

ResponseBody json(
  int status,
  Map<String, dynamic> body, {
  Map<String, List<String>> headers = const {},
}) {
  return ResponseBody.fromString(
    jsonEncode(body),
    status,
    headers: {
      Headers.contentTypeHeader: [Headers.jsonContentType],
      ...headers,
    },
  );
}

const env = Env(
  apiBaseUrl: 'https://api.test/api/v1',
  reverbHost: 'h',
  reverbPort: 1,
  reverbKey: 'k',
  reverbScheme: 'http',
  mapTileUrl: 't',
  flavor: Flavor.dev,
);

(ApiClient, FakeAdapter) build(Handler h, {String? token = 'tok'}) {
  final adapter = FakeAdapter(h);
  final dio = Dio(
    BaseOptions(
      baseUrl: env.apiBaseUrl,
      validateStatus: (s) => s != null && s < 400,
    ),
  )..httpClientAdapter = adapter;
  final client = ApiClient(
    env: env,
    dio: dio,
    readToken: () => token,
    readLanguageCode: () => 'ar',
    retrySleep: (_) async {},
  );
  return (client, adapter);
}

void main() {
  test('unwraps success envelope', () async {
    final (client, _) = build(
      (_) => json(200, {
        'success': true,
        'message': 'ok',
        'data': {'a': 1},
        'meta': {'page': 2},
        'errors': {},
      }),
    );
    final r = await client.get('/x');
    expect(r.data, {'a': 1});
    expect(r.message, 'ok');
    expect(r.meta['page'], 2);
  });

  test('sends auth, locale, request-id and idempotency headers', () async {
    final (client, adapter) = build(
      (_) => json(200, {'success': true, 'data': null}),
    );
    await client.post('/bookings', body: {'a': 1}, idempotencyKey: 'key-1');
    final h = adapter.requests.single.headers;
    expect(h['Authorization'], 'Bearer tok');
    expect(h['Accept-Language'], 'ar');
    expect(h['Idempotency-Key'], 'key-1');
    expect(h['X-Request-Id'], isNotEmpty);
  });

  test(
    'parses 422 validation error with field errors and request id',
    () async {
      final (client, _) = build(
        (_) => json(422, {
          'success': false,
          'message': 'البيانات المدخلة غير صحيحة',
          'data': null,
          'meta': {'request_id': 'rid-1'},
          'errors': {
            '_code': 'validation_failed',
            'phone': ['رقم الهاتف غير صالح'],
          },
        }),
      );
      try {
        await client.post('/auth/otp/request', body: {});
        fail('should throw');
      } on ApiException catch (e) {
        expect(e.kind, ApiFailureKind.validation);
        expect(e.code, 'validation_failed');
        expect(e.statusCode, 422);
        expect(e.requestId, 'rid-1');
        expect(e.firstErrorFor('phone'), 'رقم الهاتف غير صالح');
        expect(e.fieldErrors.containsKey('_code'), isFalse);
        expect(e.message, 'البيانات المدخلة غير صحيحة');
      }
    },
  );

  test('domain 409 and 429 carry code and retry_after', () async {
    final (client, _) = build(
      (o) => o.path.contains('conflict')
          ? json(409, {
              'success': false,
              'message': 'm',
              'meta': {'request_id': 'r'},
              'errors': {'_code': 'stale_version'},
            })
          : json(429, {
              'success': false,
              'message': 'm',
              'meta': {'request_id': 'r', 'retry_after': 30},
              'errors': {'_code': 'too_many_requests'},
            }),
    );
    await expectLater(
      client.post('/conflict'),
      throwsA(
        isA<ApiException>()
            .having((e) => e.kind, 'kind', ApiFailureKind.conflict)
            .having((e) => e.code, 'code', 'stale_version'),
      ),
    );
    await expectLater(
      client.post('/limited'),
      throwsA(
        isA<ApiException>()
            .having((e) => e.kind, 'kind', ApiFailureKind.rateLimited)
            .having((e) => e.retryAfterSeconds, 'retry', 30),
      ),
    );
  });

  test('non-envelope error falls back to http_<status>', () async {
    final (client, _) = build((_) => ResponseBody.fromString('<html/>', 500));
    await expectLater(
      client.post('/x'),
      throwsA(
        isA<ApiException>()
            .having((e) => e.code, 'code', 'http_500')
            .having((e) => e.kind, 'kind', ApiFailureKind.server),
      ),
    );
  });

  test(
    'authenticated 401 emits sessionExpired; unauthenticated does not',
    () async {
      final body = {
        'success': false,
        'message': 'm',
        'meta': {'request_id': 'r'},
        'errors': {'_code': 'unauthenticated'},
      };
      final (client, _) = build((_) => json(401, body));
      var count = 0;
      client.sessionExpired.listen((_) => count++);
      await expectLater(client.get('/me'), throwsA(isA<ApiException>()));
      await Future<void>.delayed(Duration.zero);
      expect(count, 1);

      final (anon, _) = build((_) => json(401, body), token: null);
      var anonCount = 0;
      anon.sessionExpired.listen((_) => anonCount++);
      await expectLater(anon.post('/login'), throwsA(isA<ApiException>()));
      await Future<void>.delayed(Duration.zero);
      expect(anonCount, 0);
    },
  );

  test('GET retries on 503 then succeeds; POST never retries', () async {
    var calls = 0;
    final (client, adapter) = build((_) {
      calls++;
      return calls < 3
          ? json(503, {
              'success': false,
              'errors': {'_code': 'service_unavailable'},
            })
          : json(200, {'success': true, 'data': 1});
    });
    final r = await client.get('/x');
    expect(r.data, 1);
    expect(adapter.requests.length, 3);

    calls = 0;
    adapter.requests.clear();
    await expectLater(client.post('/x'), throwsA(isA<ApiException>()));
    expect(adapter.requests.length, 1);
  });

  test(
    'transport failure maps to network exception after GET retries',
    () async {
      final (client, adapter) = build(
        (o) => throw DioException.connectionError(
          requestOptions: o,
          reason: 'down',
        ),
      );
      await expectLater(
        client.get('/x'),
        throwsA(
          isA<ApiException>().having((e) => e.isOffline, 'offline', true),
        ),
      );
      expect(adapter.requests.length, 3);
    },
  );

  test('newIdempotencyKey returns distinct UUIDs', () {
    final a = newIdempotencyKey();
    expect(a, matches(RegExp(r'^[0-9a-f-]{36}$')));
    expect(a, isNot(newIdempotencyKey()));
  });
}
