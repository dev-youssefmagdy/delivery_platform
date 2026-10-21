import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import '../../fakes.dart';
import '../../helpers.dart';

const _prefs = {'language_code': 'en', 'onboarding_done': true};

ProviderContainer? _current;

/// A widget test that also stops tracking and the live connection before it
/// ends (both outlive the screen by design, so a timer would still be pending).
void _scenario(String name, Future<void> Function(WidgetTester) body) {
  testWidgets(name, (tester) async {
    await body(tester);
    await tester.pumpWidget(const SizedBox());
    _current?.dispose();
  });
}

class _Rig {
  _Rig(this.availability, this.location, this.tracking, this.realtime);

  late final ProviderContainer container;

  final FakeAvailabilityRepository availability;
  final FakeLocationService location;
  final FakeTrackingSource tracking;
  final FakeRealtimeClient realtime;
}

Future<_Rig> _pump(
  WidgetTester tester, {
  FakeAvailabilityRepository? availability,
  FakeLocationService? location,
  List<Map<String, dynamic>>? vehicles,
  Map<String, Object> prefs = _prefs,
}) async {
  tester.view.physicalSize = const Size(900, 3000);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  final rig = _Rig(
    availability ?? FakeAvailabilityRepository(),
    location ?? FakeLocationService(),
    FakeTrackingSource(),
    FakeRealtimeClient(),
  );
  _current = rig.container = await pumpApp(
    tester,
    prefs: prefs,
    token: 'abc',
    provider: FakeProviderRepository(
      application: applicationJson(
        status: 'approved',
        editable: false,
        steps: {'profile': true},
        vehicles: vehicles ?? [vehicleJson(status: 'approved')],
      ),
    ),
    availability: rig.availability,
    location: rig.location,
    tracking: rig.tracking,
    realtime: rig.realtime,
  );
  return rig;
}

Finder _switch() => find.byKey(const Key('availability-switch'));

void main() {
  _scenario('offline: shows the switch off, tracking off and no reasons', (
    tester,
  ) async {
    await _pump(tester);

    expect(find.text('You are offline'), findsOneWidget);
    expect(tester.widget<SwitchListTile>(_switch()).value, isFalse);
    expect(find.byKey(const Key('tracking-off')), findsOneWidget);
    expect(find.byKey(const Key('availability-reasons')), findsNothing);
    expect(find.byKey(const Key('availability-error')), findsNothing);
  });

  _scenario(
    'going online asks with a rationale first, then permission, then goes online and tracks',
    (tester) async {
      final rig = await _pump(
        tester,
        location: FakeLocationService(
          permission: LocationPermissionState.denied,
        )..grantOnRequest = LocationPermissionState.granted,
      );

      await tester.tap(_switch());
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('location-rationale')), findsOneWidget);
      expect(find.text('Why we need your location'), findsOneWidget);
      expect(rig.location.requests, 0, reason: 'the OS prompt comes after');

      await tester.tap(find.byKey(const Key('location-rationale-continue')));
      await tester.pumpAndSettle();

      expect(rig.location.requests, 1);
      expect(rig.availability.onlineRequests, hasLength(1));
      expect(rig.availability.onlineRequests.single.location.latitude, 30.05);
      expect(find.text('You are online'), findsOneWidget);
      expect(tester.widget<SwitchListTile>(_switch()).value, isTrue);
      expect(find.byKey(const Key('tracking-idle')), findsOneWidget);
      expect(find.text('Updates every 30 s'), findsOneWidget);
      expect(rig.tracking.starts, [const Duration(seconds: 30)]);
      expect(rig.tracking.notices.single.title, 'You are online');
    },
  );

  _scenario(
    'a driver who is not asked twice: granted permission skips the rationale',
    (tester) async {
      final rig = await _pump(tester);

      await tester.tap(_switch());
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('location-rationale')), findsNothing);
      expect(rig.availability.online, isTrue);
    },
  );

  _scenario('declining the rationale keeps the driver offline and explains', (
    tester,
  ) async {
    final rig = await _pump(
      tester,
      location: FakeLocationService(permission: LocationPermissionState.denied),
    );

    await tester.tap(_switch());
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('location-rationale-cancel')));
    await tester.pumpAndSettle();

    expect(rig.availability.calls, isNot(contains('online')));
    expect(find.byKey(const Key('availability-permission')), findsOneWidget);
    expect(find.text('Allow location access to go online.'), findsOneWidget);
    expect(tester.widget<SwitchListTile>(_switch()).value, isFalse);
    expect(rig.tracking.starts, isEmpty);
  });

  _scenario('blocked location: a button opens the app settings', (
    tester,
  ) async {
    final rig = await _pump(
      tester,
      location: FakeLocationService(
        permission: LocationPermissionState.deniedForever,
      ),
    );

    await tester.tap(_switch());
    await tester.pumpAndSettle();

    expect(
      find.text('Location is blocked for this app. Open settings to allow it.'),
      findsOneWidget,
    );
    await tester.tap(find.byKey(const Key('availability-open-settings')));
    expect(rig.location.settingsOpened, 1);
  });

  _scenario('location services off: a button opens the location settings', (
    tester,
  ) async {
    final rig = await _pump(
      tester,
      location: FakeLocationService(
        permission: LocationPermissionState.serviceDisabled,
      ),
    );

    await tester.tap(_switch());
    await tester.pumpAndSettle();

    expect(
      find.text('Location services are turned off on this device.'),
      findsOneWidget,
    );
    await tester.tap(find.byKey(const Key('availability-open-settings')));
    expect(rig.location.locationSettingsOpened, 1);
  });

  _scenario('no position fix says so and stays offline', (tester) async {
    final rig = await _pump(
      tester,
      location: FakeLocationService()..throwOnCurrent = true,
    );

    await tester.tap(_switch());
    await tester.pumpAndSettle();

    expect(rig.availability.calls, isNot(contains('online')));
    expect(
      find.text(
        'We could not get your position. Try again where the signal is better.',
      ),
      findsOneWidget,
    );
  });

  _scenario('the blocking reasons are listed once and the switch is disabled', (
    tester,
  ) async {
    await _pump(
      tester,
      availability: FakeAvailabilityRepository(
        eligibility: const EligibilityInfo(
          eligible: false,
          issues: [
            EligibilityIssueInfo(reason: EligibilityReason.noApprovedVehicle),
            EligibilityIssueInfo(
              reason: EligibilityReason.cashDebtLimitExceeded,
            ),
            EligibilityIssueInfo(
              reason: EligibilityReason.cashDebtLimitExceeded,
            ),
            EligibilityIssueInfo(
              reason: EligibilityReason.locationStale,
              reference: 'missing',
            ),
          ],
        ),
      ),
    );

    expect(find.byKey(const Key('availability-reasons')), findsOneWidget);
    expect(find.text('There is no approved vehicle.'), findsOneWidget);
    expect(
      find.text(
        'Your unsettled commission is over the allowed limit. Settle it to go online.',
      ),
      findsOneWidget,
    );
    expect(find.textContaining('missing or out of date'), findsNothing);
    expect(tester.widget<SwitchListTile>(_switch()).onChanged, isNull);
  });

  _scenario('a stale location alone does not block the switch', (tester) async {
    await _pump(
      tester,
      availability: FakeAvailabilityRepository(
        eligibility: const EligibilityInfo(
          eligible: false,
          issues: [
            EligibilityIssueInfo(
              reason: EligibilityReason.locationStale,
              reference: 'missing',
            ),
          ],
        ),
      ),
    );

    expect(find.byKey(const Key('availability-reasons')), findsNothing);
    expect(tester.widget<SwitchListTile>(_switch()).onChanged, isNotNull);
  });

  _scenario(
    'the server refusing with typed reasons shows them and stays offline',
    (tester) async {
      final availability = FakeAvailabilityRepository()
        ..onlineError = const ApiException(
          kind: ApiFailureKind.validation,
          code: 'provider_not_eligible',
          message: 'no',
          statusCode: 422,
          meta: {
            'reasons': [
              {'reason': 'active_job_exists', 'reference': null},
            ],
          },
        );
      await _pump(tester, availability: availability);

      await tester.tap(_switch());
      await tester.pumpAndSettle();

      expect(find.text('You already have an active job.'), findsOneWidget);
      expect(tester.widget<SwitchListTile>(_switch()).value, isFalse);
    },
  );

  _scenario(
    'outside the service area and an unusable vehicle have their own texts',
    (tester) async {
      final availability = FakeAvailabilityRepository()
        ..onlineError = const ApiException(
          kind: ApiFailureKind.validation,
          code: 'outside_service_area',
          message: null,
          statusCode: 422,
        );
      await _pump(tester, availability: availability);

      await tester.tap(_switch());
      await tester.pumpAndSettle();
      expect(find.text('You are outside the service areas.'), findsOneWidget);

      availability.onlineError = const ApiException(
        kind: ApiFailureKind.validation,
        code: 'vehicle_not_usable',
        message: null,
        statusCode: 422,
      );
      await tester.tap(_switch());
      await tester.pumpAndSettle();
      expect(
        find.text('This vehicle cannot be used right now.'),
        findsOneWidget,
      );
    },
  );

  _scenario('going offline stops tracking', (tester) async {
    final rig = await _pump(
      tester,
      availability: FakeAvailabilityRepository(online: true),
    );
    expect(find.byKey(const Key('tracking-idle')), findsOneWidget);
    expect(rig.tracking.starts, hasLength(1));

    await tester.tap(_switch());
    await tester.pumpAndSettle();

    expect(find.text('You are offline'), findsOneWidget);
    expect(find.byKey(const Key('tracking-off')), findsOneWidget);
    expect(rig.tracking.cancelled, 1);
    expect(rig.tracking.listening, isFalse);
  });

  _scenario(
    'with a job in progress, offline needs a confirmation and tracking continues',
    (tester) async {
      final rig = await _pump(
        tester,
        availability: FakeAvailabilityRepository(
          online: true,
          activeBookingId: 'b-1',
        )..intervalSeconds = 5,
      );
      expect(find.byKey(const Key('availability-active-job')), findsOneWidget);
      expect(find.byKey(const Key('tracking-active')), findsOneWidget);

      await tester.tap(_switch());
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('offline-confirm')), findsOneWidget);

      // Staying online sends nothing further.
      await tester.tap(find.byKey(const Key('offline-confirm-stay')));
      await tester.pumpAndSettle();
      expect(rig.availability.online, isTrue);
      expect(rig.availability.offlineConfirmations, [false]);

      await tester.tap(_switch());
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('offline-confirm-go')));
      await tester.pumpAndSettle();

      expect(rig.availability.offlineConfirmations, [false, false, true]);
      expect(rig.availability.online, isFalse);
      expect(
        find.byKey(const Key('tracking-active')),
        findsOneWidget,
        reason: 'the job keeps being tracked',
      );
      expect(rig.tracking.cancelled, 0);
    },
  );

  _scenario('with several approved vehicles the chosen one is sent', (
    tester,
  ) async {
    final rig = await _pump(
      tester,
      vehicles: [
        vehicleJson(id: 'veh-1', status: 'approved'),
        vehicleJson(id: 'veh-2', status: 'approved'),
        vehicleJson(id: 'veh-3', status: 'draft'),
      ],
    );
    expect(find.byKey(const Key('availability-vehicle')), findsOneWidget);

    await tester.tap(find.byKey(const Key('availability-vehicle')));
    await tester.pumpAndSettle();
    await tester.tap(find.textContaining('Toyota Corolla').last);
    await tester.pumpAndSettle();
    await tester.tap(_switch());
    await tester.pumpAndSettle();

    expect(rig.availability.onlineRequests.single.vehicleId, 'veh-2');
  });

  _scenario('a single approved vehicle shows no picker', (tester) async {
    await _pump(tester);

    expect(find.byKey(const Key('availability-vehicle')), findsNothing);
  });

  _scenario('the reason a driver was taken offline is explained', (
    tester,
  ) async {
    await _pump(
      tester,
      availability: FakeAvailabilityRepository()
        ..lastOfflineReason = OfflineReason.staleLocation,
    );

    expect(
      find.text(
        'You were taken offline because your location stopped updating.',
      ),
      findsOneWidget,
    );
  });

  _scenario('a failed read shows a retry that works', (tester) async {
    final availability = FakeAvailabilityRepository()
      ..snapshotError = const ApiException(
        kind: ApiFailureKind.network,
        code: 'network_error',
        message: null,
      );
    await _pump(tester, availability: availability);

    expect(find.byKey(const Key('availability-switch')), findsNothing);
    availability.snapshotError = null;
    await tester.tap(find.text('Try again'));
    await tester.pumpAndSettle();

    expect(find.byKey(const Key('availability-switch')), findsOneWidget);
  });

  _scenario('Arabic renders right-to-left with Arabic texts', (tester) async {
    await _pump(
      tester,
      prefs: const {'language_code': 'ar', 'onboarding_done': true},
    );

    expect(find.text('أنت غير متصل'), findsOneWidget);
    expect(directionOf(tester, _switch()), TextDirection.rtl);
  });
}
