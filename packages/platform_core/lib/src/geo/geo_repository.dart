import 'package:latlong2/latlong.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import '../enums/saved_place_type.dart';
import 'geo_models.dart';

/// Geography API for both apps (docs/API.md 8.7): service area resolution,
/// place search, reverse geocoding, route estimates and saved places. Only
/// code that knows these endpoints; throws [ApiException].
class GeoRepository {
  const GeoRepository(this.api);

  final ApiClient api;

  Future<ServiceAreaInfo> resolveArea(LatLng point) async =>
      ServiceAreaInfo.fromJson(
        _map(
          await api.get(
            '/service-areas/resolve',
            query: {'lat': point.latitude, 'lng': point.longitude},
          ),
        ),
      );

  /// Type-ahead search, ranked near [near] when given.
  Future<List<PlaceResult>> searchPlaces(
    String query, {
    LatLng? near,
    int? limit,
  }) async {
    final result = await api.get(
      '/places/search',
      query: {
        'q': query,
        'lat': ?near?.latitude,
        'lng': ?near?.longitude,
        'limit': ?limit,
      },
    );
    return [for (final item in _list(result)) ?PlaceResult.tryFromJson(item)];
  }

  /// The address at [point]; null when the provider knows nothing there.
  Future<PlaceResult?> reverse(LatLng point) async {
    final data = (await api.get(
      '/places/reverse',
      query: {'lat': point.latitude, 'lng': point.longitude},
    )).data;
    return data is Map
        ? PlaceResult.tryFromJson(Map<String, dynamic>.from(data))
        : null;
  }

  Future<RouteEstimateInfo> estimateRoute({
    required LatLng origin,
    required LatLng destination,
    int? serviceCategoryId,
  }) async => RouteEstimateInfo.fromJson(
    _map(
      await api.get(
        '/routes/estimate',
        query: {
          'origin': '${origin.latitude},${origin.longitude}',
          'destination': '${destination.latitude},${destination.longitude}',
          'service_category_id': ?serviceCategoryId,
        },
      ),
    ),
  );

  Future<List<SavedPlaceInfo>> savedPlaces() async => [
    for (final item in _list(await api.get('/me/saved-places')))
      ?SavedPlaceInfo.tryFromJson(item),
  ];

  Future<SavedPlaceInfo> createSavedPlace({
    required String label,
    required SavedPlaceType type,
    required LatLng point,
    PlaceResult? place,
  }) async => _saved(
    await api.post(
      '/me/saved-places',
      body: _savedBody(label, type, point, place),
    ),
  );

  Future<SavedPlaceInfo> updateSavedPlace(
    String id, {
    required String label,
    required SavedPlaceType type,
    required LatLng point,
    PlaceResult? place,
  }) async => _saved(
    await api.put(
      '/me/saved-places/$id',
      body: _savedBody(label, type, point, place),
    ),
  );

  Future<void> deleteSavedPlace(String id) async {
    await api.delete('/me/saved-places/$id');
  }

  Map<String, Object?> _savedBody(
    String label,
    SavedPlaceType type,
    LatLng point,
    PlaceResult? place,
  ) => {
    'label': label,
    'type': type.value,
    'lat': point.latitude,
    'lng': point.longitude,
    if (place != null)
      'address': {
        'title': place.title,
        'line': place.address,
        'precision': place.precision.value,
        'provider': place.provider,
      },
  };

  SavedPlaceInfo _saved(ApiResult<Object?> result) =>
      SavedPlaceInfo.tryFromJson(_map(result)) ?? _invalid();

  Map<String, dynamic> _map(ApiResult<Object?> result) {
    final data = result.data;
    if (data is! Map) _invalid();
    return Map<String, dynamic>.from(data);
  }

  List<Map<String, dynamic>> _list(ApiResult<Object?> result) {
    final data = result.data;
    if (data is! List) _invalid();
    return [
      for (final item in data)
        if (item is Map) Map<String, dynamic>.from(item),
    ];
  }

  Never _invalid() => throw const ApiException(
    kind: ApiFailureKind.server,
    code: 'invalid_response',
    message: null,
  );
}
