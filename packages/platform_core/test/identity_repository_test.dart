import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_client_test.dart' show FakeAdapter, json, env;

const _userJson = {
  'id': 'u-1',
  'app': 'customer',
  'name': null,
  'phone': '+201001234567',
  'status': 'deletion_pending',
  'profile_complete': false,
  'consents': {
    'pending': [
      {'id': 'p-1', 'type': 'terms', 'version': 2},
    ],
  },
  'customer_profile': {'marketing_opt_in': true},
  'deletion_request': {'id': 'd-1', 'status': 'pending'},
};

void main() {
  late FakeAdapter adapter;
  late IdentityRepository repo;

  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    final prefs = await SharedPreferences.getInstance();
    adapter = FakeAdapter(
      (o) => json(200, {
        'success': true,
        'data': o.path.endsWith('/otp/request')
            ? {'expires_in': 300, 'resend_in': 45, 'length': 6}
            : {'token': 'secret', 'is_new_user': true, 'user': _userJson},
      }),
    );
    final dio = Dio(BaseOptions(baseUrl: env.apiBaseUrl))
      ..httpClientAdapter = adapter;
    repo = IdentityRepository(
      api: ApiClient(
        env: env,
        dio: dio,
        readToken: () => null,
        readLanguageCode: () => 'ar',
      ),
      app: ClientApp.provider,
      deviceId: DeviceIdStore(prefs),
      appVersion: () async => '1.2.3',
      platformName: 'android',
    );
  });

  test(
    'requestOtp sends the app header, phone and a stable device id',
    () async {
      final result = await repo.requestOtp('+201001234567');
      expect(result.resendInSeconds, 45);
      final req = adapter.requests.single;
      expect(req.headers['X-App'], 'provider');
      final body = req.data as Map;
      expect(body['phone'], '+201001234567');
      await repo.requestOtp('+201001234567');
      expect(
        (adapter.requests.last.data as Map)['device_id'],
        body['device_id'],
      );
    },
  );

  test('verifyOtp parses token, status and pending consents', () async {
    final session = await repo.verifyOtp('+201001234567', '123456');
    expect(session.token, 'secret');
    expect(session.isNewUser, isTrue);
    expect(session.user.status, UserStatus.deletionPending);
    expect(session.user.pendingConsents.single.id, 'p-1');
    expect(session.user.customerProfile!.marketingOptIn, isTrue);
    expect(session.user.deletionRequest!.id, 'd-1');
    final body = jsonDecode(jsonEncode(adapter.requests.single.data)) as Map;
    expect((body['device'] as Map)['app_version'], '1.2.3');
    expect((body['device'] as Map)['platform'], 'android');
  });

  test('unknown status maps to unknown', () {
    expect(
      MeUser.fromJson({..._userJson, 'status': 'brand_new'}).status,
      UserStatus.unknown,
    );
  });

  test('account deletion sends the idempotency key', () async {
    await repo.requestAccountDeletion(idempotencyKey: 'k-1', reason: 'x');
    expect(adapter.requests.single.headers['Idempotency-Key'], 'k-1');
  });
}
