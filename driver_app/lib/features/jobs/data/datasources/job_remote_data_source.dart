import 'package:platform_core/platform_core.dart';

/// Raw calls of the provider request, offer and trip endpoints (docs/API.md
/// 8.12, 8.13) through the shared ApiClient.
class JobRemoteDataSource {
  const JobRemoteDataSource(this._api);

  final ApiClient _api;

  Future<ApiResult<Object?>> available() =>
      _api.get('/provider/requests/available');

  Future<ApiResult<Object?>> offers() => _api.get('/provider/offers');

  Future<ApiResult<Object?>> acceptCustomerFare(
    String id,
    Map<String, Object?> body,
    String key,
  ) => _api.post(
    '/provider/bookings/$id/offers/accept-customer-fare',
    body: body,
    idempotencyKey: key,
  );

  Future<ApiResult<Object?>> counter(
    String id,
    Map<String, Object?> body,
    String key,
  ) => _api.post(
    '/provider/bookings/$id/offers',
    body: body,
    idempotencyKey: key,
  );

  Future<ApiResult<Object?>> withdraw(String offerId) =>
      _api.post('/provider/offers/$offerId/withdraw');

  Future<ApiResult<Object?>> skip(String id) =>
      _api.post('/provider/bookings/$id/skip');

  Future<ApiResult<Object?>> active() => _api.get('/provider/bookings/active');

  Future<ApiResult<Object?>> job(String id) =>
      _api.get('/provider/bookings/$id');

  Future<ApiResult<Object?>> transition(
    String id,
    String step,
    String key, {
    Map<String, Object?>? body,
  }) => _api.post(
    '/provider/bookings/$id/$step',
    body: body,
    idempotencyKey: key,
  );

  Future<ApiResult<Object?>> cancel(
    String id,
    Map<String, Object?> body, {
    String? key,
  }) => _api.post(
    '/provider/bookings/$id/cancel',
    body: body,
    idempotencyKey: key,
  );

  Future<ApiResult<Object?>> rating(
    String id,
    Map<String, Object?> body,
    String key,
  ) => _api.post(
    '/provider/bookings/$id/rating',
    body: body,
    idempotencyKey: key,
  );

  Future<ApiResult<Object?>> report(
    String id,
    Map<String, Object?> body,
    String key,
  ) => _api.post(
    '/provider/bookings/$id/reports',
    body: body,
    idempotencyKey: key,
  );
}
