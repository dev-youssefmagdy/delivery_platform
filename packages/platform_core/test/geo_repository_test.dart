import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import 'api_client_test.dart' show FakeAdapter, json, env;

/// Exact shapes produced by the backend resources (docs/API.md 8.7).
void main() {
  late FakeAdapter adapter;
  late GeoRepository repo;

  void respond(Object? data, {int status = 200, Map<String, dynamic>? error}) {
    adapter = FakeAdapter(
      (o) => json(status, error ?? {'success': true, 'data': data}),
    );
    repo = GeoRepository(
      ApiClient(
        env: env,
        dio: Dio(BaseOptions(baseUrl: env.apiBaseUrl))
          ..httpClientAdapter = adapter,
        readToken: () => 'tok',
        readLanguageCode: () => 'ar',
      ),
    );
  }

  test('resolveArea parses an area with categories', () async {
    respond({
      'in_service_area': true,
      'point': {'lat': 30.05, 'lng': 31.25},
      'city': {
        'id': 2,
        'name': {'ar': 'القاهرة', 'en': 'Cairo'},
        'timezone': 'Africa/Cairo',
        'center': {'lat': 30.04, 'lng': 31.23},
      },
      'zone': {
        'id': 5,
        'name': {'ar': 'وسط'},
        'restricted_pickup': true,
      },
      'local_time': '2026-10-20T12:00:00+03:00',
      'categories': [
        {
          'service_category_id': 7,
          'code': 'taxi',
          'available': true,
          'reason': 'available',
          'opens_at': null,
          'overrides': {},
        },
        {
          'service_category_id': 8,
          'code': 'truck',
          'available': false,
          'reason': 'zone_closed',
          'opens_at': '2026-10-21T06:00:00+03:00',
          'overrides': {},
        },
      ],
    });

    final area = await repo.resolveArea(const LatLng(30.05, 31.25));

    expect(area.inServiceArea, isTrue);
    expect(area.cityName.resolve('en'), 'Cairo');
    expect(area.zoneId, 5);
    expect(area.restrictedPickup, isTrue);
    expect(area.isCategoryAvailable(7), isTrue);
    expect(area.isCategoryAvailable(8), isFalse);
    expect(adapter.requests.single.path, endsWith('/service-areas/resolve'));
    expect(adapter.requests.single.queryParameters, {
      'lat': 30.05,
      'lng': 31.25,
    });
  });

  test('resolveArea outside every zone', () async {
    respond({
      'in_service_area': false,
      'point': {'lat': 40.0, 'lng': 10.0},
      'city': null,
      'zone': null,
      'local_time': null,
      'categories': [],
    });

    final area = await repo.resolveArea(const LatLng(40, 10));

    expect(area.inServiceArea, isFalse);
    expect(area.zoneId, isNull);
    expect(area.categories, isEmpty);
  });

  test('searchPlaces sends the bias and skips malformed rows', () async {
    respond([
      {
        'id': 'p1',
        'title': 'Tahrir Square',
        'address': 'Tahrir, Cairo',
        'lat': 30.04,
        'lng': 31.23,
        'precision': 'street',
        'provider': 'fake',
      },
      {'title': 'Broken', 'lat': null, 'lng': 31.0},
    ]);

    final places = await repo.searchPlaces(
      'tahrir',
      near: const LatLng(30.05, 31.25),
      limit: 5,
    );

    expect(places, hasLength(1));
    expect(places.single.precision, LocationPrecision.street);
    expect(places.single.isExact, isFalse);
    final q = adapter.requests.single.queryParameters;
    expect(q['q'], 'tahrir');
    expect(q['lat'], 30.05);
    expect(q['limit'], 5);
  });

  test('unknown precision values degrade instead of crashing', () async {
    respond({
      'id': null,
      'title': 'X',
      'address': 'X',
      'lat': 30.0,
      'lng': 31.0,
      'precision': 'quantum',
      'provider': 'fake',
    });

    final place = await repo.reverse(const LatLng(30, 31));

    expect(place?.precision, LocationPrecision.unknown);
    expect(place?.isExact, isFalse);
  });

  test('reverse returns null when the server has no address', () async {
    respond(null);

    expect(await repo.reverse(const LatLng(30, 31)), isNull);
  });

  test('maps outages surface as ApiException with the stable code', () async {
    respond(
      null,
      status: 503,
      error: {
        'success': false,
        'message': 'down',
        'data': null,
        'errors': {'_code': 'maps_unavailable'},
      },
    );

    await expectLater(
      repo.searchPlaces('x y'),
      throwsA(
        isA<ApiException>().having((e) => e.code, 'code', 'maps_unavailable'),
      ),
    );
  });

  test('saved places: list, create body and delete', () async {
    respond([
      {
        'id': 'a1',
        'label': 'Home',
        'type': 'home',
        'lat': 30.05,
        'lng': 31.25,
        'address': {'title': 'T', 'line': 'Tahrir', 'precision': 'exact'},
        'created_at': '2026-10-20T10:00:00Z',
      },
    ]);
    final list = await repo.savedPlaces();
    expect(list.single.type, SavedPlaceType.home);
    expect(list.single.addressLine, 'Tahrir');
    expect(list.single.precision, LocationPrecision.exact);

    respond({
      'id': 'a2',
      'label': 'Work',
      'type': 'work',
      'lat': 30.06,
      'lng': 31.26,
      'address': null,
    });
    final created = await repo.createSavedPlace(
      label: 'Work',
      type: SavedPlaceType.work,
      point: const LatLng(30.06, 31.26),
      place: const PlaceResult(
        title: 'Office',
        address: 'Office, Cairo',
        point: LatLng(30.06, 31.26),
        precision: LocationPrecision.street,
        provider: 'fake',
      ),
    );
    expect(created.id, 'a2');
    final body = jsonDecode(jsonEncode(adapter.requests.single.data)) as Map;
    expect(body['type'], 'work');
    expect(body['lat'], 30.06);
    expect((body['address'] as Map)['precision'], 'street');
    expect(adapter.requests.single.method, 'POST');

    respond(null);
    await repo.deleteSavedPlace('a2');
    expect(adapter.requests.single.method, 'DELETE');
    expect(adapter.requests.single.path, endsWith('/me/saved-places/a2'));
  });

  test('estimateRoute formats the lat,lng pairs', () async {
    respond({
      'distance_m': 5200,
      'duration_s': 700,
      'polyline': '_p~iF~ps|U',
      'provider': 'fake',
      'origin_zone_id': 5,
      'origin_city_id': 2,
    });

    final route = await repo.estimateRoute(
      origin: const LatLng(30.05, 31.25),
      destination: const LatLng(30.06, 31.26),
      serviceCategoryId: 7,
    );

    expect(route.distanceMeters, 5200);
    expect(route.durationSeconds, 700);
    final q = adapter.requests.single.queryParameters;
    expect(q['origin'], '30.05,31.25');
    expect(q['destination'], '30.06,31.26');
    expect(q['service_category_id'], 7);
  });
}
