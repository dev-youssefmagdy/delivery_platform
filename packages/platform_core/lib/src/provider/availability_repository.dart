import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import '../enums/location_source.dart';
import '../location/location_service.dart';
import 'availability_models.dart';

/// Availability and live location API (docs/API.md 8.10). The only code that
/// knows these endpoints; throws [ApiException]. Going online fails with code
/// `provider_not_eligible` (422): use [eligibilityFromMeta] on its `meta`.
class AvailabilityRepository {
  const AvailabilityRepository(this.api);

  final ApiClient api;

  Map<String, dynamic> _map(ApiResult<Object?> r) {
    final data = r.data;
    if (data is! Map) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return Map<String, dynamic>.from(data);
  }

  /// One sample in the API shape. Negative heading or speed means "unknown"
  /// on the platform APIs and is not sent.
  static Map<String, dynamic> sampleJson(GeoPoint point) {
    final heading = point.heading;
    final speed = point.speedMps;
    return {
      'lat': point.latitude,
      'lng': point.longitude,
      'recorded_at': (point.capturedAt ?? DateTime.now())
          .toUtc()
          .toIso8601String(),
      'accuracy_m': ?point.accuracyMeters,
      'heading': ?(heading == null || heading < 0 ? null : heading),
      'speed_mps': ?(speed == null || speed < 0 ? null : speed),
      'source': point.source == LocationSource.network ? 'network' : 'gps',
    };
  }

  Future<AvailabilitySnapshot> snapshot() async =>
      AvailabilitySnapshot.fromJson(
        _map(await api.get('/provider/availability')),
      );

  Future<ProviderAvailability> goOnline({
    required GeoPoint location,
    String? vehicleId,
  }) async => ProviderAvailability.fromJson(
    _map(
      await api.post(
        '/provider/availability/online',
        body: {'vehicle_id': ?vehicleId, 'location': sampleJson(location)},
      ),
    ),
  );

  /// With an active job the server answers 409 `active_job_blocks_offline`
  /// until [confirm] is true.
  Future<ProviderAvailability> goOffline({bool confirm = false}) async =>
      ProviderAvailability.fromJson(
        _map(
          await api.post(
            '/provider/availability/offline',
            body: {'confirm': confirm},
          ),
        ),
      );

  Future<LocationUploadResult> uploadLocations(List<GeoPoint> points) async =>
      LocationUploadResult.fromJson(
        _map(
          await api.post(
            '/provider/location',
            body: {
              'samples': [for (final p in points) sampleJson(p)],
            },
          ),
        ),
      );
}
