import 'package:driver_app/core/providers.dart';
import 'package:driver_app/features/availability/presentation/providers/availability_providers.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../fakes.dart';

class _Env {
  _Env({
    FakeAvailabilityRepository? availability,
    FakeLocationService? location,
  }) : availability = availability ?? FakeAvailabilityRepository(online: true),
       location = location ?? FakeLocationService();

  final FakeAvailabilityRepository availability;
  final FakeLocationService location;
  final source = FakeTrackingSource();
  final realtime = FakeRealtimeClient();

  Future<void> settle() async {
    for (var i = 0; i < 10; i++) {
      await Future<void>.delayed(Duration.zero);
    }
  }
}

void main() {
  late SharedPreferences prefs;

  Future<ProviderContainer> start(_Env e) async {
    final container = ProviderContainer(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(prefs),
        tokenStoreProvider.overrideWithValue(MemoryTokenStore('tok')),
        availabilityRepositoryProvider.overrideWithValue(e.availability),
        locationServiceProvider.overrideWithValue(e.location),
        trackingSourceProvider.overrideWithValue(e.source),
        realtimeClientProvider.overrideWithValue(e.realtime),
      ],
    );
    addTearDown(container.dispose);
    await container.read(sessionControllerProvider).restore();
    return container;
  }

  setUp(() async {
    SharedPreferences.setMockInitialValues({'language_code': 'en'});
    prefs = await SharedPreferences.getInstance();
  });

  group('TrackingController', () {
    const offline = ApiException(
      kind: ApiFailureKind.network,
      code: 'network_error',
      message: null,
    );

    /// Starts the app-level controllers the way the availability card does.
    Future<ProviderContainer> running(WidgetTester tester, _Env e) async {
      final c = await start(e);
      c.read(trackingControllerProvider);
      c.read(availabilityProvider);
      await tester.pump();
      return c;
    }

    testWidgets(
      'tracks at the interval the server asks for and uploads the latest position',
      (tester) async {
        final e = _Env();
        final c = await running(tester, e);

        expect(e.source.starts, [const Duration(seconds: 30)]);
        expect(c.read(trackingControllerProvider).running, isTrue);

        e.source.emit(GeoPoint(latitude: 30.05, longitude: 31.25));
        await tester.pump(const Duration(seconds: 29));
        expect(e.availability.uploads, isEmpty);
        await tester.pump(const Duration(seconds: 2));

        expect(e.availability.uploads, hasLength(1));
        expect(e.availability.uploads.single.single.latitude, 30.05);
        expect(c.read(trackingControllerProvider).lastUploadAt, isNotNull);
        expect(c.read(trackingControllerProvider).lastUploadFailed, isFalse);

        // The device stood still and the stream sent nothing: a fresh fix is the heartbeat, so the
        // server does not take the driver offline for a stale position.
        await tester.pump(const Duration(seconds: 30));
        expect(e.availability.uploads, hasLength(2));
        expect(e.availability.uploads.last.single.latitude, 30.05);

        e.source.emit(GeoPoint(latitude: 30.06, longitude: 31.25));
        await tester.pump(const Duration(seconds: 30));
        expect(e.availability.uploads, hasLength(3));
        expect(e.availability.uploads.last.single.latitude, 30.06);
        c.dispose();
      },
    );

    testWidgets(
      'a failed upload keeps the positions and sends them with the next one',
      (tester) async {
        final e = _Env();
        final c = await running(tester, e);

        e.availability.uploadError = offline;
        e.source.emit(GeoPoint(latitude: 30.05, longitude: 31.25));
        await tester.pump(const Duration(seconds: 30));
        expect(c.read(trackingControllerProvider).lastUploadFailed, isTrue);

        e.availability.uploadError = null;
        e.source.emit(GeoPoint(latitude: 30.06, longitude: 31.25));
        await tester.pump(const Duration(seconds: 30));

        expect(e.availability.uploads.last.map((p) => p.latitude), [
          30.05,
          30.06,
        ]);
        expect(c.read(trackingControllerProvider).lastUploadFailed, isFalse);
        c.dispose();
      },
    );

    testWidgets('the buffer is bounded', (tester) async {
      final e = _Env();
      final c = await running(tester, e);

      e.availability.uploadError = offline;
      for (var i = 0; i < TrackingController.maxBuffered + 5; i++) {
        e.source.emit(GeoPoint(latitude: 30.0 + i / 1000, longitude: 31.25));
        await tester.pump(const Duration(seconds: 30));
      }
      e.availability.uploadError = null;
      e.source.emit(GeoPoint(latitude: 30.5, longitude: 31.25));
      await tester.pump(const Duration(seconds: 30));

      expect(
        e.availability.uploads.last,
        hasLength(TrackingController.maxBuffered),
      );
      expect(e.availability.uploads.last.last.latitude, 30.5);
      c.dispose();
    });

    testWidgets(
      'a job raises the frequency and ending tracking cancels the position stream',
      (tester) async {
        final e = _Env();
        final c = await running(tester, e);
        expect(e.source.starts, [const Duration(seconds: 30)]);

        // The upload answer says a job started: the tracker re-reads and speeds up.
        e.availability
          ..activeBookingId = 'b-1'
          ..intervalSeconds = 5
          ..nextUploadPlan = const TrackingPlan(
            state: TrackingState.active,
            intervalSeconds: 5,
          );
        e.source.emit(GeoPoint(latitude: 30.05, longitude: 31.25));
        await tester.pump(const Duration(seconds: 30));
        await tester.pump();

        expect(e.source.starts, [
          const Duration(seconds: 30),
          const Duration(seconds: 5),
        ]);
        expect(
          c.read(trackingControllerProvider).plan.state,
          TrackingState.active,
        );

        // Job over and driver offline: tracking stops completely.
        e.availability
          ..activeBookingId = null
          ..nextUploadPlan = null
          ..online = false;
        await c.read(availabilityProvider.notifier).refresh();
        await tester.pump();

        expect(c.read(trackingControllerProvider).running, isFalse);
        expect(e.source.listening, isFalse);
        final uploads = e.availability.uploads.length;
        await tester.pump(const Duration(minutes: 2));
        expect(
          e.availability.uploads,
          hasLength(uploads),
          reason: 'no uploads while offline',
        );
        c.dispose();
      },
    );

    testWidgets(
      'the server saying the driver is not tracked re-reads the availability',
      (tester) async {
        final e = _Env();
        final c = await running(tester, e);

        e.availability
          ..online = false
          ..uploadError = const ApiException(
            kind: ApiFailureKind.conflict,
            code: 'location_not_tracking',
            message: null,
            statusCode: 409,
          );
        e.source.emit(GeoPoint(latitude: 30.05, longitude: 31.25));
        await tester.pump(const Duration(seconds: 30));
        await tester.pump();

        expect(
          c.read(availabilityProvider).value!.availability.online,
          isFalse,
        );
        expect(c.read(trackingControllerProvider).running, isFalse);
        c.dispose();
      },
    );

    testWidgets('lost permission is reported instead of tracking', (
      tester,
    ) async {
      final e = _Env(
        location: FakeLocationService(
          permission: LocationPermissionState.deniedForever,
        ),
      );
      final c = await running(tester, e);

      expect(e.source.starts, isEmpty);
      expect(c.read(trackingControllerProvider).running, isFalse);
      expect(
        c.read(trackingControllerProvider).permission,
        LocationPermissionState.deniedForever,
      );
      c.dispose();
    });
  });

  group('RealtimeController', () {
    test('subscribes to the private provider channel and re-reads on every (re)connect', () async {
      final e = _Env(availability: FakeAvailabilityRepository());
      final c = await start(e);
      c.read(realtimeControllerProvider);
      await c.read(availabilityProvider.future);
      await e.settle();

      expect(e.realtime.subscribed, ['provider.prov-1']);
      expect(e.realtime.connects, 1);
      expect(
        c.read(realtimeControllerProvider).connection,
        RealtimeConnectionState.connected,
      );

      final reads = e.availability.calls.where((c) => c == 'snapshot').length;
      e.realtime.setState(RealtimeConnectionState.disconnected);
      await e.settle();
      expect(
        c.read(realtimeControllerProvider).connection,
        RealtimeConnectionState.disconnected,
      );

      e.realtime.setState(RealtimeConnectionState.connecting);
      await e.settle();
      expect(
        c.read(realtimeControllerProvider).connection,
        RealtimeConnectionState.connecting,
      );

      e.availability.online = true;
      e.realtime.reconnect();
      await e.settle();

      expect(
        c.read(realtimeControllerProvider).connection,
        RealtimeConnectionState.connected,
      );
      expect(
        e.availability.calls.where((c) => c == 'snapshot').length,
        greaterThan(reads),
      );
      expect(c.read(availabilityProvider).value!.availability.online, isTrue);
    });

    test('an availability event refreshes the snapshot; other events go to the bus', () async {
      final e = _Env();
      final c = await start(e);
      c.read(realtimeControllerProvider);
      await c.read(availabilityProvider.future);
      await e.settle();
      final seen = <String>[];
      c
          .read(providerRealtimeEventsProvider)
          .stream
          .listen((ev) => seen.add(ev.name));

      e.availability.online = false;
      e.availability.lastOfflineReason = OfflineReason.accountChanged;
      e.realtime.emit('provider.availability_changed', {
        'sequence': 1,
        'payload': {'online': false},
      });
      await e.settle();

      expect(c.read(availabilityProvider).value!.availability.online, isFalse);
      expect(
        c.read(availabilityProvider).value!.availability.lastOfflineReason,
        OfflineReason.accountChanged,
      );

      e.realtime.emit('booking.requested', {
        'booking_uuid': 'b-1',
        'sequence': 1,
        'payload': {},
      });
      e.realtime.emit('pusher_internal:subscription_succeeded', {});
      await e.settle();
      expect(seen, ['provider.availability_changed', 'booking.requested']);
    });

    test('a gap in the sequence numbers re-reads the snapshot', () async {
      final e = _Env();
      final c = await start(e);
      c.read(realtimeControllerProvider);
      await c.read(availabilityProvider.future);
      await e.settle();

      e.realtime.emit('booking.requested', {
        'booking_uuid': 'b-1',
        'sequence': 1,
      });
      await e.settle();
      final reads = e.availability.calls.where((c) => c == 'snapshot').length;

      e.realtime.emit('booking.requested', {
        'booking_uuid': 'b-1',
        'sequence': 2,
      });
      await e.settle();
      expect(
        e.availability.calls.where((c) => c == 'snapshot').length,
        reads,
        reason: 'in order',
      );

      e.realtime.emit('booking.search_expanding', {
        'booking_uuid': 'b-1',
        'sequence': 5,
      });
      await e.settle();
      expect(
        e.availability.calls.where((c) => c == 'snapshot').length,
        reads + 1,
      );
    });

    test('a connection that cannot be opened shows disconnected', () async {
      final e = _Env(availability: FakeAvailabilityRepository());
      e.realtime.connectError = StateError('refused');
      final c = await start(e);
      c.read(realtimeControllerProvider);
      await c.read(availabilityProvider.future);
      await e.settle();

      expect(
        c.read(realtimeControllerProvider).connection,
        RealtimeConnectionState.disconnected,
      );
    });

    test('signing out closes the connection and stops tracking', () async {
      final e = _Env();
      final c = await start(e);
      c.read(realtimeControllerProvider);
      c.read(trackingControllerProvider);
      await c.read(availabilityProvider.future);
      await e.settle();
      expect(e.source.listening, isTrue);

      await c.read(sessionControllerProvider).signOut();
      await e.settle();

      expect(e.realtime.unsubscribed, ['provider.prov-1']);
      expect(e.realtime.disconnects, greaterThan(0));
      expect(e.source.listening, isFalse);
      expect(
        c.read(realtimeControllerProvider).connection,
        RealtimeConnectionState.disconnected,
      );
    });
  });
}
