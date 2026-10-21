import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import 'api_client_test.dart' show FakeAdapter, json, env;

/// Exact shapes produced by the backend (docs/API.md 8.10).
const _availability = {
  'online': true,
  'online_since': '2026-10-23T08:00:00Z',
  'vehicle_id': 'veh-1',
  'last_offline_reason': null,
  'active_booking_id': null,
  'tracking': {
    'state': 'idle',
    'interval_seconds': 30,
    'stale_after_seconds': 90,
  },
  'realtime': {'channel': 'private-provider.p-1'},
};

void main() {
  late FakeAdapter adapter;
  late AvailabilityRepository repo;

  setUp(() {
    adapter = FakeAdapter((o) {
      if (o.path.endsWith('/provider/availability') && o.method == 'GET') {
        return json(200, {
          'success': true,
          'data': {
            'availability': {
              ..._availability,
              'online': false,
              'tracking': {'state': 'off', 'interval_seconds': 0},
            },
            'eligibility': {
              'eligible': false,
              'reasons': [
                {'reason': 'location_stale', 'reference': 'missing'},
                {'reason': 'cash_debt_limit_exceeded', 'reference': null},
                {'reason': 'brand_new_reason', 'reference': null},
              ],
            },
          },
        });
      }
      if (o.path.endsWith('/availability/online')) {
        final body = o.data as Map<String, dynamic>;
        if (body['vehicle_id'] == 'busy') {
          return json(422, {
            'success': false,
            'message': 'no',
            'errors': {'_code': 'provider_not_eligible'},
            'meta': {
              'reasons': [
                {'reason': 'active_job_exists', 'reference': null},
              ],
            },
          });
        }
        return json(200, {'success': true, 'data': _availability});
      }
      if (o.path.endsWith('/availability/offline')) {
        return json(200, {
          'success': true,
          'data': {
            ..._availability,
            'online': false,
            'last_offline_reason': 'provider_choice',
          },
        });
      }
      if (o.path.endsWith('/provider/location')) {
        return json(200, {
          'success': true,
          'data': {
            'received': 2,
            'applied': 1,
            'flagged': 1,
            'stale': 0,
            'tracking': {'state': 'active', 'interval_seconds': 5},
          },
        });
      }
      return json(404, {'success': false, 'message': 'x'});
    });
    repo = AvailabilityRepository(
      ApiClient(
        env: env,
        dio: Dio(BaseOptions(baseUrl: env.apiBaseUrl))
          ..httpClientAdapter = adapter,
        readToken: () => 'tok',
        readLanguageCode: () => 'ar',
      ),
    );
  });

  test('the snapshot parses availability, tracking and eligibility', () async {
    final snapshot = await repo.snapshot();

    expect(snapshot.availability.online, isFalse);
    expect(snapshot.availability.tracking.state, TrackingState.off);
    expect(snapshot.availability.tracking.isTracking, isFalse);
    expect(snapshot.eligibility.eligible, isFalse);
    expect(snapshot.eligibility.reasons, [
      EligibilityReason.locationStale,
      EligibilityReason.cashDebtLimitExceeded,
      EligibilityReason.unknown,
    ]);
  });

  test('going online sends the position and parses the answer', () async {
    final result = await repo.goOnline(
      vehicleId: 'veh-1',
      location: GeoPoint(
        latitude: 30.05,
        longitude: 31.25,
        accuracyMeters: 8,
        heading: -1,
        speedMps: 3.5,
        capturedAt: DateTime.utc(2026, 10, 23, 8),
      ),
    );

    expect(result.online, isTrue);
    expect(result.vehicleId, 'veh-1');
    expect(result.tracking.intervalSeconds, 30);
    expect(result.tracking.staleAfterSeconds, 90);
    expect(result.channel, 'private-provider.p-1');
    expect(result.channelName, 'provider.p-1');
    expect(result.onlineSince, DateTime.utc(2026, 10, 23, 8));

    final request = adapter.requests.last;
    expect(request.path, endsWith('/provider/availability/online'));
    expect(request.method, 'POST');
    final body = request.data as Map<String, dynamic>;
    expect(body['vehicle_id'], 'veh-1');
    final location = body['location'] as Map<String, dynamic>;
    expect(location['lat'], 30.05);
    expect(location['lng'], 31.25);
    expect(location['recorded_at'], '2026-10-23T08:00:00.000Z');
    expect(location['accuracy_m'], 8);
    expect(location['speed_mps'], 3.5);
    expect(
      location.containsKey('heading'),
      isFalse,
      reason: 'negative = unknown',
    );
    expect(location['source'], 'gps');
  });

  test('the typed reasons of a 422 are on the exception meta', () async {
    final point = GeoPoint(latitude: 30.05, longitude: 31.25);

    await expectLater(
      repo.goOnline(location: point, vehicleId: 'busy'),
      throwsA(
        isA<ApiException>()
            .having((e) => e.code, 'code', 'provider_not_eligible')
            .having((e) => e.statusCode, 'status', 422)
            .having((e) => eligibilityFromMeta(e.meta).reasons, 'reasons', [
              EligibilityReason.activeJobExists,
            ]),
      ),
    );
  });

  test('going offline sends the confirmation flag', () async {
    final result = await repo.goOffline(confirm: true);

    expect(result.online, isFalse);
    expect(result.lastOfflineReason, OfflineReason.providerChoice);
    expect(jsonEncode(adapter.requests.last.data), '{"confirm":true}');
  });

  test(
    'a location batch is sent as samples and the new plan is parsed',
    () async {
      final result = await repo.uploadLocations([
        GeoPoint(
          latitude: 30.05,
          longitude: 31.25,
          source: LocationSource.network,
        ),
        GeoPoint(latitude: 30.06, longitude: 31.26),
      ]);

      expect(result.received, 2);
      expect(result.flagged, 1);
      expect(result.tracking.state, TrackingState.active);
      expect(result.tracking.intervalSeconds, 5);
      final samples = (adapter.requests.last.data as Map)['samples'] as List;
      expect(samples, hasLength(2));
      expect((samples[0] as Map)['source'], 'network');
      expect((samples[1] as Map)['source'], 'gps');
    },
  );

  test('unknown tracking states and reasons never crash', () {
    expect(TrackingState.parse('flying'), TrackingState.off);
    expect(OfflineReason.parse('mystery'), OfflineReason.unknown);
    expect(
      ProviderAvailability.fromJson(const {'online': false})
          .tracking
          .isTracking,
      isFalse,
    );
  });
}
