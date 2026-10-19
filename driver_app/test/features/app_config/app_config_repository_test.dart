import 'package:driver_app/features/app_config/data/datasources/app_config_remote_data_source.dart';
import 'package:driver_app/features/app_config/data/repositories/app_config_repository_impl.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:platform_core/platform_core.dart';

class MockApiClient extends Mock implements ApiClient {}

void main() {
  late MockApiClient api;
  late AppConfigRepositoryImpl repo;

  setUp(() {
    api = MockApiClient();
    repo = AppConfigRepositoryImpl(AppConfigRemoteDataSource(api));
  });

  test('maps the envelope data to an entity', () async {
    when(() => api.get('/config/app')).thenAnswer(
      (_) async => const ApiResult<Object?>(
        data: {
          'default_locale': 'ar',
          'locales': ['ar', 'en', 'fr'],
          'currency': {'code': 'EGP', 'exponent': 2},
          'digits_preference': 'arabic_indic',
          'maintenance': true,
          'min_version': '1.2.0',
          'something_new': 1,
        },
      ),
    );
    final c = await repo.fetch();
    expect(c.languageCodes, ['ar', 'en']);
    expect(c.currencyCode, 'EGP');
    expect(c.digitStyle, DigitStyle.arabicIndic);
    expect(c.maintenance, isTrue);
    expect(c.minVersion, '1.2.0');
  });

  test('missing fields fall back to safe defaults', () async {
    when(() => api.get('/config/app')).thenAnswer(
      (_) async => const ApiResult<Object?>(data: <String, dynamic>{}),
    );
    final c = await repo.fetch();
    expect(c.defaultLanguageCode, 'ar');
    expect(c.languageCodes, ['ar', 'en']);
    expect(c.digitStyle, isNull);
    expect(c.maintenance, isFalse);
  });

  test('propagates ApiException', () async {
    when(() => api.get('/config/app')).thenThrow(
      const ApiException(
        kind: ApiFailureKind.network,
        code: 'network_error',
        message: null,
      ),
    );
    expect(repo.fetch(), throwsA(isA<ApiException>()));
  });

  test('non-object data is an invalid_response error', () async {
    when(() => api.get('/config/app'))
        .thenAnswer((_) async => const ApiResult<Object?>(data: null));
    expect(
      repo.fetch(),
      throwsA(
        isA<ApiException>().having((e) => e.code, 'code', 'invalid_response'),
      ),
    );
  });
}
