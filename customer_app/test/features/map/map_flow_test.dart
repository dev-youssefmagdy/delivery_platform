import 'package:customer_app/features/map/domain/place_selection.dart';
import 'package:customer_app/features/map/presentation/providers/map_providers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import '../../fakes.dart';
import '../../helpers.dart';
import '../catalog/catalog_fixtures.dart';

const _en = {'language_code': 'en', 'onboarding_done': true};
const _ar = {'language_code': 'ar', 'onboarding_done': true};

const _fix = GeoPoint(latitude: 30.05, longitude: 31.25);

PlaceResult _place(
  String title, {
  LocationPrecision precision = LocationPrecision.street,
  double lat = 30.04,
  double lng = 31.23,
}) => PlaceResult(
  id: 'p-$title',
  title: title,
  address: '$title, Cairo',
  point: LatLng(lat, lng),
  precision: precision,
  provider: 'fake',
);

const _outage = ApiException(
  kind: ApiFailureKind.server,
  code: 'maps_unavailable',
  message: null,
  statusCode: 503,
);

const _offline = ApiException(
  kind: ApiFailureKind.network,
  code: 'network_error',
  message: null,
);

Future<void> _openSearch(WidgetTester tester, Key field) async {
  await tester.tap(find.byKey(field));
  await tester.pumpAndSettle();
}

Future<void> _type(WidgetTester tester, String text) async {
  await tester.enterText(find.byKey(const Key('place-search-field')), text);
  await tester.pump(const Duration(milliseconds: 400));
  await tester.pumpAndSettle();
}

void main() {
  group('location permission', () {
    testWidgets('denied: rationale first, nothing requested until allowed', (
      tester,
    ) async {
      final location = FakeLocationService()
        ..grantOnRequest = LocationPermissionState.granted
        ..position = _fix;
      final geo = FakeGeoRepository();
      await pumpApp(
        tester,
        prefs: _en,
        token: 't',
        location: location,
        geo: geo,
      );

      expect(find.byKey(const Key('location-banner')), findsOneWidget);
      expect(find.text('Choose pickup manually'), findsOneWidget);
      expect(location.requests, 0, reason: 'no OS prompt without a rationale');

      await tester.tap(find.byKey(const Key('location-action')));
      await tester.pumpAndSettle();
      expect(find.text('Use your location?'), findsOneWidget);

      await tester.tap(find.byKey(const Key('rationale-not-now')));
      await tester.pumpAndSettle();
      expect(location.requests, 0);
      expect(find.byKey(const Key('location-banner')), findsOneWidget);

      await tester.tap(find.byKey(const Key('location-action')));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('rationale-allow')));
      await tester.pumpAndSettle();

      expect(location.requests, 1);
      expect(find.byKey(const Key('location-banner')), findsNothing);
      expect(find.text('Current location'), findsOneWidget);
      expect(find.byKey(const Key('map-user-marker')), findsOneWidget);
      expect(find.byKey(const Key('map-pickup-marker')), findsOneWidget);
      expect(geo.resolvedPoints.single.latitude, closeTo(30.05, 1e-6));
    });

    testWidgets('granted at start: pickup is the device position', (
      tester,
    ) async {
      final catalog = FakeCatalogRepository();
      await pumpApp(
        tester,
        prefs: _en,
        token: 't',
        catalog: catalog,
        location: FakeLocationService(
          permission: LocationPermissionState.granted,
          position: _fix,
        ),
      );

      expect(find.text('Current location'), findsOneWidget);
      expect(find.byKey(const Key('location-banner')), findsNothing);
      expect(catalog.lastLat, closeTo(30.05, 1e-6));
      expect(catalog.lastLng, closeTo(31.25, 1e-6));
    });

    testWidgets('blocked forever: offers settings and a manual fallback', (
      tester,
    ) async {
      final location = FakeLocationService(
        permission: LocationPermissionState.deniedForever,
      );
      await pumpApp(tester, prefs: _en, token: 't', location: location);

      expect(find.textContaining('blocked for this app'), findsOneWidget);
      await tester.tap(find.byKey(const Key('location-action')));
      await tester.pumpAndSettle();
      expect(location.settingsOpened, 1);
      expect(find.byKey(const Key('location-manual')), findsOneWidget);
    });

    testWidgets('device location switched off', (tester) async {
      await pumpApp(
        tester,
        prefs: _en,
        token: 't',
        location: FakeLocationService(
          permission: LocationPermissionState.serviceDisabled,
        ),
      );

      expect(
        find.textContaining('device location is switched off'),
        findsOneWidget,
      );
    });

    testWidgets('granted but no fix: error with retry and manual choice', (
      tester,
    ) async {
      final location = FakeLocationService(
        permission: LocationPermissionState.granted,
        position: _fix,
      )..throwOnCurrent = true;
      await pumpApp(tester, prefs: _en, token: 't', location: location);

      expect(
        find.textContaining('could not get your position'),
        findsOneWidget,
      );

      location.throwOnCurrent = false;
      await tester.tap(find.byKey(const Key('location-action')));
      await tester.pumpAndSettle();
      expect(find.text('Current location'), findsOneWidget);
    });

    testWidgets(
      'manual fallback: choosing pickup by search works without GPS',
      (tester) async {
        final geo = FakeGeoRepository()
          ..searchResults = [_place('Tahrir Square')];
        await pumpApp(tester, prefs: _en, token: 't', geo: geo);

        await tester.tap(find.byKey(const Key('location-manual')));
        await tester.pumpAndSettle();
        await _type(tester, 'tahrir');
        await tester.tap(find.text('Tahrir Square'));
        await tester.pumpAndSettle();

        expect(find.byKey(const Key('location-banner')), findsNothing);
        expect(find.text('Tahrir Square'), findsOneWidget);
        expect(geo.resolvedPoints, isNotEmpty);
      },
    );
  });

  group('place search', () {
    testWidgets('debounces typing into one request and shows results', (
      tester,
    ) async {
      final geo = FakeGeoRepository()
        ..searchResults = [_place('Tahrir Square'), _place('Tahrir Bridge')];
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await _openSearch(tester, const Key('destination-field'));

      final field = find.byKey(const Key('place-search-field'));
      await tester.enterText(field, 'ta');
      await tester.pump(const Duration(milliseconds: 100));
      await tester.enterText(field, 'tah');
      await tester.pump(const Duration(milliseconds: 100));
      await tester.enterText(field, 'tahr');
      expect(geo.searchQueries, isEmpty);
      await tester.pump(const Duration(milliseconds: 400));
      await tester.pumpAndSettle();

      expect(geo.searchQueries, ['tahr']);
      expect(find.text('Tahrir Square'), findsOneWidget);
      expect(find.text('Tahrir Bridge'), findsOneWidget);
    });

    testWidgets('a single character does not search', (tester) async {
      final geo = FakeGeoRepository();
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await _openSearch(tester, const Key('destination-field'));

      await _type(tester, 't');

      expect(geo.searchQueries, isEmpty);
      expect(
        find.text('Type at least 2 characters to search.'),
        findsOneWidget,
      );
    });

    testWidgets('approximate results are labelled, never shown as exact', (
      tester,
    ) async {
      final geo = FakeGeoRepository()
        ..searchResults = [
          _place('Zamalek', precision: LocationPrecision.approximate),
          _place('12 Tahrir St', precision: LocationPrecision.exact),
        ];
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await _openSearch(tester, const Key('destination-field'));
      await _type(tester, 'za');

      expect(find.textContaining('Approximate position'), findsOneWidget);
    });

    testWidgets('empty results and the map shortcut', (tester) async {
      await pumpApp(tester, prefs: _en, token: 't');
      await _openSearch(tester, const Key('destination-field'));
      await _type(tester, 'zzzz');

      expect(find.text('No places found'), findsOneWidget);
      expect(find.byKey(const Key('search-on-map')), findsOneWidget);
    });

    testWidgets('maps outage keeps the map choice available', (tester) async {
      final geo = FakeGeoRepository()..searchError = _outage;
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await _openSearch(tester, const Key('destination-field'));
      await _type(tester, 'tahrir');

      expect(
        find.textContaining('Search is temporarily unavailable'),
        findsOneWidget,
      );
      expect(find.byKey(const Key('search-on-map')), findsOneWidget);
    });

    testWidgets('offline search shows the offline state and retries', (
      tester,
    ) async {
      final geo = FakeGeoRepository()..searchError = _offline;
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await _openSearch(tester, const Key('destination-field'));
      await _type(tester, 'tahrir');

      expect(find.text('You are offline'), findsOneWidget);

      geo
        ..searchError = null
        ..searchResults = [_place('Tahrir Square')];
      await tester.tap(find.text('Try again'));
      await tester.pump(const Duration(milliseconds: 400));
      await tester.pumpAndSettle();
      expect(find.text('Tahrir Square'), findsOneWidget);
    });

    testWidgets('saved places and current location are one tap away', (
      tester,
    ) async {
      final geo = FakeGeoRepository(
        saved: [
          SavedPlaceInfo(
            id: 's1',
            label: 'Home',
            type: SavedPlaceType.home,
            point: const LatLng(30.06, 31.26),
            addressLine: 'Nile St',
          ),
        ],
      );
      await pumpApp(
        tester,
        prefs: _en,
        token: 't',
        geo: geo,
        location: FakeLocationService(
          permission: LocationPermissionState.granted,
          position: _fix,
        ),
      );
      await _openSearch(tester, const Key('destination-field'));

      expect(find.byKey(const Key('search-saved-s1')), findsOneWidget);
      expect(find.byKey(const Key('search-use-current')), findsNothing);

      await tester.tap(find.byKey(const Key('search-saved-s1')));
      await tester.pumpAndSettle();
      expect(
        find.descendant(
          of: find.byKey(const Key('destination-field')),
          matching: find.text('Home'),
        ),
        findsOneWidget,
      );
    });
  });

  group('trip endpoints', () {
    testWidgets('swap exchanges pickup and destination', (tester) async {
      final geo = FakeGeoRepository()
        ..searchResults = [_place('Alpha'), _place('Beta', lat: 30.06)];
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);

      final swap = find.byKey(const Key('swap-endpoints'));
      expect(tester.widget<IconButton>(swap).onPressed, isNull);

      await _openSearch(tester, const Key('pickup-field'));
      await _type(tester, 'al');
      await tester.tap(find.text('Alpha'));
      await tester.pumpAndSettle();

      await _openSearch(tester, const Key('destination-field'));
      await _type(tester, 'be');
      await tester.tap(find.text('Beta'));
      await tester.pumpAndSettle();

      String text(Key k) => tester
          .widget<Text>(
            find
                .descendant(of: find.byKey(k), matching: find.byType(Text))
                .first,
          )
          .data!;
      expect(text(const Key('pickup-field')), 'Alpha');
      expect(text(const Key('destination-field')), 'Beta');
      expect(tester.widget<IconButton>(swap).onPressed, isNotNull);

      await tester.tap(swap);
      await tester.pumpAndSettle();

      expect(text(const Key('pickup-field')), 'Beta');
      expect(text(const Key('destination-field')), 'Alpha');
      expect(find.byKey(const Key('map-destination-marker')), findsOneWidget);
    });
  });

  group('service area', () {
    Future<ProviderContainer> pumpWithPickup(
      WidgetTester tester,
      FakeGeoRepository geo, {
      FakeCatalogRepository? catalog,
    }) async {
      final container = await pumpApp(
        tester,
        prefs: _en,
        token: 't',
        geo: geo,
        catalog: catalog,
      );
      container
          .read(tripEndpointsProvider.notifier)
          .setPickup(
            const PlaceSelection(point: LatLng(40, 10), title: 'Far away'),
          );
      await tester.pumpAndSettle();
      return container;
    }

    testWidgets('outside every zone: explains and offers another pickup', (
      tester,
    ) async {
      final geo = FakeGeoRepository()
        ..area = const ServiceAreaInfo(inServiceArea: false);
      await pumpWithPickup(tester, geo);

      expect(find.byKey(const Key('area-outside')), findsOneWidget);
      expect(find.text('Outside our service area'), findsOneWidget);
      expect(find.byKey(const Key('area-change-pickup')), findsOneWidget);
    });

    testWidgets('a failing area check shows an error with retry', (
      tester,
    ) async {
      final geo = FakeGeoRepository()..areaError = _offline;
      await pumpWithPickup(tester, geo);

      expect(find.byKey(const Key('area-error')), findsOneWidget);
      expect(find.text('Check your connection and try again.'), findsWidgets);

      geo.areaError = null;
      await tester.tap(
        find.descendant(
          of: find.byKey(const Key('area-error')),
          matching: find.text('Try again'),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byKey(const Key('area-error')), findsNothing);
    });

    testWidgets('restricted pickup is announced', (tester) async {
      final geo = FakeGeoRepository()
        ..area = const ServiceAreaInfo(
          inServiceArea: true,
          zoneId: 1,
          restrictedPickup: true,
        );
      await pumpWithPickup(tester, geo);

      expect(find.byKey(const Key('area-restricted')), findsOneWidget);
    });

    testWidgets('the catalog is requested for the pickup position', (
      tester,
    ) async {
      final catalog = FakeCatalogRepository(
        familiesResult: [family(1, 'rides', 'رحلات', 'Rides')],
      );
      await pumpWithPickup(tester, FakeGeoRepository(), catalog: catalog);

      expect(catalog.lastLat, 40.0);
      expect(catalog.lastLng, 10.0);
    });
  });

  group('pin adjust', () {
    Future<void> openPin(WidgetTester tester) async {
      await _openSearch(tester, const Key('pickup-field'));
      await tester.tap(find.byKey(const Key('search-on-map')));
      await tester.pumpAndSettle();
    }

    testWidgets('shows the address and confirms the pinned point', (
      tester,
    ) async {
      final geo = FakeGeoRepository()
        ..reverseResult = _place(
          '12 Tahrir St',
          precision: LocationPrecision.exact,
        );
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await openPin(tester);

      expect(find.byKey(const Key('pin-marker')), findsOneWidget);
      expect(find.text('12 Tahrir St'), findsOneWidget);
      expect(find.byKey(const Key('pin-approximate')), findsNothing);

      await tester.tap(find.byKey(const Key('pin-confirm')));
      await tester.pumpAndSettle();

      expect(find.text('12 Tahrir St'), findsOneWidget);
      expect(find.byKey(const Key('pickup-field')), findsOneWidget);
    });

    testWidgets('an approximate address is flagged', (tester) async {
      final geo = FakeGeoRepository()
        ..reverseResult = _place(
          'Zamalek',
          precision: LocationPrecision.approximate,
        );
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await openPin(tester);

      expect(find.byKey(const Key('pin-approximate')), findsOneWidget);
    });

    testWidgets('failed lookup does not block confirming', (tester) async {
      final geo = FakeGeoRepository()..reverseError = _outage;
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await openPin(tester);

      expect(find.byKey(const Key('pin-lookup-failed')), findsOneWidget);
      await tester.tap(find.byKey(const Key('pin-confirm')));
      await tester.pumpAndSettle();
      expect(find.text('Pinned location'), findsOneWidget);
    });

    testWidgets('no address for the point', (tester) async {
      await pumpApp(tester, prefs: _en, token: 't');
      await openPin(tester);

      expect(find.byKey(const Key('pin-no-address')), findsOneWidget);
    });

    testWidgets('outside the service area is called out for a pickup', (
      tester,
    ) async {
      final geo = FakeGeoRepository()
        ..area = const ServiceAreaInfo(inServiceArea: false);
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await openPin(tester);

      expect(find.byKey(const Key('pin-outside')), findsOneWidget);
    });
  });

  group('saved places', () {
    Future<void> openSaved(WidgetTester tester) async {
      await tester.ensureVisible(find.byKey(const Key('home-saved-places')));
      await tester.tap(find.byKey(const Key('home-saved-places')));
      await tester.pumpAndSettle();
    }

    testWidgets('empty state', (tester) async {
      await pumpApp(tester, prefs: _en, token: 't');
      await openSaved(tester);

      expect(find.text('No saved places yet'), findsOneWidget);
      expect(find.byKey(const Key('saved-place-add')), findsOneWidget);
    });

    testWidgets('lists, adds and deletes', (tester) async {
      final geo = FakeGeoRepository(
        saved: [
          SavedPlaceInfo(
            id: 's1',
            label: 'Home',
            type: SavedPlaceType.home,
            point: const LatLng(30.06, 31.26),
            addressLine: 'Nile St',
          ),
        ],
      )..searchResults = [_place('Office Tower')];
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await openSaved(tester);

      expect(find.text('Home'), findsOneWidget);
      expect(find.text('Nile St'), findsOneWidget);

      await tester.tap(find.byKey(const Key('saved-place-add')));
      await tester.pumpAndSettle();
      await _type(tester, 'office');
      await tester.tap(find.text('Office Tower'));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('saved-place-name')), findsOneWidget);
      await tester.enterText(find.byKey(const Key('saved-place-name')), '');
      await tester.tap(find.byKey(const Key('saved-place-save')));
      await tester.pumpAndSettle();
      expect(find.text('Enter a name'), findsOneWidget);

      await tester.enterText(find.byKey(const Key('saved-place-name')), 'Work');
      await tester.tap(find.byKey(const Key('saved-place-save')));
      await tester.pumpAndSettle();
      expect(geo.created.single['label'], 'Work');
      expect(find.text('Work'), findsOneWidget);

      await tester.tap(find.byKey(const Key('saved-place-delete-s1')));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Delete'));
      await tester.pumpAndSettle();
      expect(geo.deleted, ['s1']);
      expect(find.byKey(const Key('saved-place-s1')), findsNothing);
    });

    testWidgets('server rule errors are explained', (tester) async {
      final geo = FakeGeoRepository()
        ..searchResults = [_place('Office Tower')]
        ..createError = const ApiException(
          kind: ApiFailureKind.validation,
          code: 'saved_place_type_taken',
          message: null,
          statusCode: 422,
        );
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await openSaved(tester);
      await tester.tap(find.byKey(const Key('saved-place-add')));
      await tester.pumpAndSettle();
      await _type(tester, 'office');
      await tester.tap(find.text('Office Tower'));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const Key('saved-place-save')));
      await tester.pumpAndSettle();

      expect(
        find.text('You already saved a place of this type. Delete it first.'),
        findsOneWidget,
      );
    });

    testWidgets('a failing list shows retry and recovers', (tester) async {
      final geo = FakeGeoRepository()..savedError = _offline;
      await pumpApp(tester, prefs: _en, token: 't', geo: geo);
      await openSaved(tester);

      expect(find.text('You are offline'), findsOneWidget);

      geo.savedError = null;
      await tester.tap(find.text('Try again'));
      await tester.pumpAndSettle();
      expect(find.text('No saved places yet'), findsOneWidget);
    });
  });

  testWidgets('Arabic home map panel is RTL with Arabic labels', (
    tester,
  ) async {
    await pumpApp(tester, prefs: _ar, token: 't');

    expect(find.text('من أين؟'), findsOneWidget);
    expect(find.text('إلى أين؟'), findsOneWidget);
    expect(
      directionOf(tester, find.byKey(const Key('pickup-field'))),
      TextDirection.rtl,
    );
    expect(find.text('اختيار نقطة الاستلام يدوياً'), findsOneWidget);
  });
}
