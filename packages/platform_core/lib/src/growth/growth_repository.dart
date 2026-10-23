import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import 'growth_models.dart';

ApiException _invalid() => const ApiException(
  kind: ApiFailureKind.server,
  code: 'invalid_response',
  message: null,
);

Map<String, dynamic> _obj(ApiResult<Object?> r) {
  final data = r.data;
  if (data is! Map) throw _invalid();
  return Map<String, dynamic>.from(data);
}

/// Referrals, credits, loyalty and provider incentives (docs/API.md 8.30).
/// Every call answers 404 `feature_disabled` while its server flag is off; the
/// apps do not even show the screens then. [applyReferral] takes the
/// Idempotency-Key of its user intent and the caller keeps it across retries.
abstract interface class GrowthRepository {
  Future<ReferralOverview> referral();

  Future<void> applyReferral(String code, {required String key});

  Future<CreditsOverview> credits();

  Future<LoyaltyStatus> loyalty();

  Future<List<IncentiveQuest>> incentives();
}

class GrowthRemoteDataSource {
  const GrowthRemoteDataSource(this.api);

  final ApiClient api;

  Future<ApiResult<Object?>> referral() => api.get('/me/referral');

  Future<ApiResult<Object?>> apply(String code, String key) =>
      api.post('/me/referral/apply', body: {'code': code}, idempotencyKey: key);

  Future<ApiResult<Object?>> credits() => api.get('/me/credits');

  Future<ApiResult<Object?>> loyalty() => api.get('/me/loyalty');

  Future<ApiResult<Object?>> incentives() => api.get('/provider/incentives');
}

class ApiGrowthRepository implements GrowthRepository {
  const ApiGrowthRepository(this._remote);

  final GrowthRemoteDataSource _remote;

  @override
  Future<ReferralOverview> referral() async =>
      ReferralOverview.fromJson(_obj(await _remote.referral()));

  @override
  Future<void> applyReferral(String code, {required String key}) async {
    await _remote.apply(code, key);
  }

  @override
  Future<CreditsOverview> credits() async =>
      CreditsOverview.fromJson(_obj(await _remote.credits()));

  @override
  Future<LoyaltyStatus> loyalty() async =>
      LoyaltyStatus.fromJson(_obj(await _remote.loyalty()));

  @override
  Future<List<IncentiveQuest>> incentives() async {
    final data = _obj(await _remote.incentives())['campaigns'];
    return [
      if (data is List)
        for (final e in data)
          if (e is Map) IncentiveQuest.fromJson(Map<String, dynamic>.from(e)),
    ];
  }
}
