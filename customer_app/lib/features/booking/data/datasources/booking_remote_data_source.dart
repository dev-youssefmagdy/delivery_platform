import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import '../../domain/entities/booking_entities.dart';

/// Raw calls of the customer booking endpoints through the shared ApiClient.
class BookingRemoteDataSource {
  const BookingRemoteDataSource(this._api);

  final ApiClient _api;

  Future<ApiResult<Object?>> estimate({
    required LatLng origin,
    required LatLng destination,
    required int serviceCategoryId,
    required Map<String, Object?> serviceDetails,
  }) => _api.get(
    '/routes/estimate',
    query: {
      'origin': '${origin.latitude},${origin.longitude}',
      'destination': '${destination.latitude},${destination.longitude}',
      'service_category_id': serviceCategoryId,
      for (final e in serviceDetails.entries)
        if (e.value != null && e.value is! Map && e.value is! List)
          'service_details[${e.key}]': e.value,
    },
  );

  Future<ApiResult<Object?>> create(Map<String, Object?> body, String key) =>
      _api.post('/bookings', body: body, idempotencyKey: key);

  Future<ApiResult<Object?>> get(String id) => _api.get('/bookings/$id');

  Future<ApiResult<Object?>> list({
    required int page,
    required TripFilter filter,
    bool inFlight = false,
    int perPage = 20,
  }) => _api.get(
    '/bookings',
    query: {
      'page': page,
      'per_page': perPage,
      if (inFlight) 'in_flight': 1,
      if (filter.statuses.isNotEmpty)
        'status': [for (final s in filter.statuses) s.value],
      'service_category_id': ?filter.serviceCategoryId,
      if (filter.from != null) 'from': _day(filter.from!),
      if (filter.to != null) 'to': _day(filter.to!),
    },
  );

  Future<ApiResult<Object?>> offers(String id) =>
      _api.get('/bookings/$id/offers');

  Future<ApiResult<Object?>> accept(String id, String offerId, String key) =>
      _api.post('/bookings/$id/offers/$offerId/accept', idempotencyKey: key);

  Future<ApiResult<Object?>> reject(String id, String offerId) =>
      _api.post('/bookings/$id/offers/$offerId/reject');

  Future<ApiResult<Object?>> updateRequest(
    String id,
    Map<String, Object?> body,
    String key,
  ) => _api.patch('/bookings/$id/request', body: body, idempotencyKey: key);

  Future<ApiResult<Object?>> cancel(
    String id,
    Map<String, Object?> body, {
    String? key,
  }) => _api.post('/bookings/$id/cancel', body: body, idempotencyKey: key);

  Future<ApiResult<Object?>> startShare(String id, String key) =>
      _api.post('/bookings/$id/share', idempotencyKey: key);

  Future<ApiResult<Object?>> stopShare(String id, String key) =>
      _api.delete('/bookings/$id/share', idempotencyKey: key);

  Future<ApiResult<Object?>> rate(
    String id,
    Map<String, Object?> body,
    String key,
  ) => _api.post('/bookings/$id/rating', body: body, idempotencyKey: key);

  Future<ApiResult<Object?>> report(
    String id,
    Map<String, Object?> body,
    String key,
  ) => _api.post('/bookings/$id/reports', body: body, idempotencyKey: key);

  static String _day(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
}
