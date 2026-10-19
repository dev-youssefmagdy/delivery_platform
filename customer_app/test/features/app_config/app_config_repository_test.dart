import 'package:customer_app/features/app_config/data/datasources/app_config_local_data_source.dart';
import 'package:customer_app/features/app_config/data/datasources/app_config_remote_data_source.dart';
import 'package:customer_app/features/app_config/data/repositories/app_config_repository_impl.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:platform_core/platform_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

class MockApiClient extends Mock implements ApiClient {}

/// The exact `data` object `GET /api/v1/config/app` returns (docs/API.md §8.4).
const serverConfig = <String, Object?>{
  'default_locale': 'ar',
  'locales': ['ar', 'en', 'fr'],
  'country': {'iso2': 'EG', 'phone_code': '+20', 'timezone': 'Africa/Cairo'},
  'currency': {
    'code': 'EGP',
    'exponent': 2,
    'symbol': {'ar': 'ج.م', 'en': 'EGP'},
  },
  'digits_preference': 'arabic_indic',
  'support': {'phone': '+20 100 000 0000', 'email': null, 'whatsapp': null},
  'maintenance': true,
  'apps': {
    'customer': {'min_version': '1.2.0', 'force_update': true},
    'provider': {'min_version': '9.0.0', 'force_update': false},
  },
  'policies': {
    'terms': {
      'id': '5a0f8d2c-0000-4000-8000-000000000001',
      'version': 3,
      'published_at': '2026-10-19T10:00:00Z',
    },
  },
  'features': {'something_new': true},
  'unknown_future_key': 1,
};

void main() {
  late MockApiClient api;
  late SharedPreferences prefs;
  late AppConfigRepositoryImpl repo;

  setUp(() async {
    api = MockApiClient();
    SharedPreferences.setMockInitialValues({});
    prefs = await SharedPreferences.getInstance();
    repo = AppConfigRepositoryImpl(
      AppConfigRemoteDataSource(api),
      AppConfigLocalDataSource(prefs),
    );
  });

  void serverReturns(Object? data) =>
      when(() => api.get('/config/app'))
          .thenAnswer((_) async => ApiResult<Object?>(data: data));

  test('maps the real server response to an entity for this app', () async {
    serverReturns(serverConfig);
    final c = await repo.fetch();

    expect(c.languageCodes, ['ar', 'en']);
    expect(c.defaultLanguageCode, 'ar');
    expect(c.countryCode, 'EG');
    expect(c.phoneCode, '+20');
    expect(c.timezone, 'Africa/Cairo');
    expect(c.currencyCode, 'EGP');
    expect(c.currencyExponent, 2);
    expect(c.currencySymbols, ('EGP', 'ج.م'));
    expect(c.moneySymbols, {'EGP': ('EGP', 'ج.م')});
    expect(c.digitStyle, DigitStyle.arabicIndic);
    expect(c.support.lines, ['+20 100 000 0000']);
    expect(c.maintenance, isTrue);
    // This is the customer app: it reads the `customer` rule, not `provider`.
    expect(c.minVersion, '1.2.0');
    expect(c.forceUpdate, isTrue);
    expect(c.policies['terms']!.version, 3);
    expect(c.policies['terms']!.publishedAt, DateTime.utc(2026, 10, 19, 10));
    expect(c.fromCache, isFalse);
  });

  test('a provider-keyed repository reads the provider rule', () async {
    serverReturns(serverConfig);
    final providerRepo = AppConfigRepositoryImpl(
      AppConfigRemoteDataSource(api),
      AppConfigLocalDataSource(prefs),
      appKey: 'provider',
    );
    final c = await providerRepo.fetch();
    expect(c.minVersion, '9.0.0');
    expect(c.forceUpdate, isFalse);
  });

  test('empty objects and missing fields fall back to safe defaults', () async {
    serverReturns(<String, dynamic>{});
    final c = await repo.fetch();

    expect(c.defaultLanguageCode, 'ar');
    expect(c.languageCodes, ['ar', 'en']);
    expect(c.countryCode, 'EG');
    expect(c.currencyCode, 'EGP');
    expect(c.digitStyle, isNull);
    expect(c.support.lines, isEmpty);
    expect(c.maintenance, isFalse);
    expect(c.minVersion, isNull);
    expect(c.forceUpdate, isFalse);
    expect(c.policies, isEmpty);
    expect(c.gateFor('1.0.0'), AppGateStatus.open);
  });

  test('a successful fetch is cached verbatim', () async {
    serverReturns(serverConfig);
    await repo.fetch();
    final cached = AppConfigLocalDataSource(prefs).read();
    expect(cached?['default_locale'], 'ar');
    expect(cached?['apps'], isA<Map<String, dynamic>>());
  });

  test(
    'falls back to the cached copy when the server is unreachable',
    () async {
      serverReturns(serverConfig);
      await repo.fetch();

      when(() => api.get('/config/app')).thenThrow(
        const ApiException(
          kind: ApiFailureKind.network,
          code: 'network_error',
          message: null,
        ),
      );
      final c = await repo.fetch();
      expect(c.fromCache, isTrue);
      expect(c.minVersion, '1.2.0');
      expect(c.maintenance, isTrue);
    },
  );

  test('propagates the error when there is no cache', () async {
    when(() => api.get('/config/app')).thenThrow(
      const ApiException(
        kind: ApiFailureKind.network,
        code: 'network_error',
        message: null,
      ),
    );
    expect(repo.fetch(), throwsA(isA<ApiException>()));
  });

  test('cancellation is never masked by the cache', () async {
    serverReturns(serverConfig);
    await repo.fetch();
    when(() => api.get('/config/app')).thenThrow(
      const ApiException(
        kind: ApiFailureKind.cancelled,
        code: 'cancelled',
        message: null,
      ),
    );
    expect(repo.fetch(), throwsA(isA<ApiException>()));
  });

  test('non-object data is an invalid_response error', () async {
    serverReturns(null);
    expect(
      repo.fetch(),
      throwsA(
        isA<ApiException>().having((e) => e.code, 'code', 'invalid_response'),
      ),
    );
  });

  test(
    'a malformed object is an invalid_response error, not a crash',
    () async {
      serverReturns({'currency': 'EGP'});
      expect(
        repo.fetch(),
        throwsA(
          isA<ApiException>().having((e) => e.code, 'code', 'invalid_response'),
        ),
      );
    },
  );

  test('a corrupt cache entry is ignored', () async {
    SharedPreferences.setMockInitialValues({
      AppConfigLocalDataSource.key: '{not json',
    });
    prefs = await SharedPreferences.getInstance();
    final corrupted = AppConfigRepositoryImpl(
      AppConfigRemoteDataSource(api),
      AppConfigLocalDataSource(prefs),
    );
    when(() => api.get('/config/app')).thenThrow(
      const ApiException(
        kind: ApiFailureKind.timeout,
        code: 'timeout',
        message: null,
      ),
    );
    expect(corrupted.fetch(), throwsA(isA<ApiException>()));
  });
}
