import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

import '../api/api_client.dart';
import '../api/api_result.dart';
import '../api/interceptors.dart';
import 'identity_models.dart';

/// Stable per-install id sent with sign-in so the server can keep one token
/// and push token per device (not a hardware identifier).
class DeviceIdStore {
  DeviceIdStore(this._prefs);

  static const _key = 'device_id';
  final SharedPreferences _prefs;

  String get id {
    final existing = _prefs.getString(_key);
    if (existing != null && existing.isNotEmpty) return existing;
    final created = const Uuid().v4();
    _prefs.setString(_key, created);
    return created;
  }
}

/// Identity API (docs/API.md §8.5) for both apps; the only code that knows
/// these endpoints. Throws `ApiException`.
class IdentityRepository {
  IdentityRepository({
    required this.api,
    required this.app,
    required this.deviceId,
    required this.appVersion,
    String? platformName,
  }) : _platform = platformName ?? _detectPlatform();

  final ApiClient api;
  final ClientApp app;
  final DeviceIdStore deviceId;
  final Future<String> Function() appVersion;
  final String _platform;

  static String _detectPlatform() {
    if (kIsWeb) return 'web';
    return Platform.isIOS ? 'ios' : 'android';
  }

  Map<String, String> get _appHeader => {'X-App': app.value};

  Map<String, dynamic> _map(ApiResult<Object?> r) =>
      Map<String, dynamic>.from(r.data! as Map);

  Future<OtpRequestResult> requestOtp(String phoneE164) async {
    final r = await api.post(
      '/auth/otp/request',
      body: {'phone': phoneE164, 'device_id': deviceId.id},
      headers: _appHeader,
    );
    return OtpRequestResult.fromJson(_map(r));
  }

  Future<AuthSessionInfo> verifyOtp(String phoneE164, String code) async {
    final r = await api.post(
      '/auth/otp/verify',
      body: {
        'phone': phoneE164,
        'code': code,
        'device': {
          'device_id': deviceId.id,
          'platform': _platform,
          'app_version': await appVersion(),
        },
      },
      headers: _appHeader,
    );
    return AuthSessionInfo.fromJson(_map(r));
  }

  Future<MeUser> me() async => MeUser.fromJson(_map(await api.get('/me')));

  Future<MeUser> updateProfile(ProfileUpdate update) async =>
      MeUser.fromJson(_map(await api.patch('/me', body: update.toJson())));

  Future<PolicyDocument> policy(String type) async =>
      PolicyDocument.fromJson(_map(await api.get('/policies/$type')));

  /// Accepts the given policy version ids (and optionally the separate
  /// marketing opt-in). Returns nothing; reload `/me` for the new state.
  Future<void> acceptConsents(
    List<String> policyIds, {
    bool? marketingOptIn,
  }) async {
    await api.post(
      '/me/consents',
      body: {'policies': policyIds, 'marketing_opt_in': ?marketingOptIn},
    );
  }

  Future<void> logout() async {
    await api.post('/auth/logout');
  }

  /// Creates a deletion request. Pass one [idempotencyKey] per user intent.
  Future<void> requestAccountDeletion({
    required String idempotencyKey,
    String? reason,
  }) async {
    await api.post(
      '/me/account-deletion-requests',
      body: {'reason': ?reason},
      idempotencyKey: idempotencyKey,
    );
  }

  Future<void> cancelAccountDeletion(String requestId) async {
    await api.delete('/me/account-deletion-requests/$requestId');
  }

  /// A fresh key for one deletion intent.
  static String newIntentKey() => newIdempotencyKey();
}
