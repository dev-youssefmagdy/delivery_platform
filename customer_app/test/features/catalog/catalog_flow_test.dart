import 'package:customer_app/features/catalog/presentation/providers/catalog_providers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import '../../fakes.dart';
import '../../helpers.dart';
import 'catalog_fixtures.dart';

const _en = {'language_code': 'en', 'onboarding_done': true};
const _ar = {'language_code': 'ar', 'onboarding_done': true};

FakeCatalogRepository _catalog() => FakeCatalogRepository(
  familiesResult: [
    family(1, 'rides', 'رحلات', 'Rides'),
    family(2, 'courier', 'توصيل', 'Courier', kind: 'courier', icon: 'box'),
  ],
  categoriesResult: [
    category(
      10,
      1,
      'تاكسي',
      'Taxi',
      fields: [passengersField, notesField],
      maxLuggage: 2,
    ),
    category(11, 1, 'سيارة اقتصادية', 'Economy car', minFare: 2000),
    category(
      20,
      2,
      'توصيل بالدراجة',
      'Motorcycle courier',
      icon: 'motorcycle',
      maxPassengers: null,
      maxWeightKg: '20.00',
      negotiation: false,
    ),
  ],
);

void main() {
  testWidgets('home lists the first family\'s categories from the API', (
    tester,
  ) async {
    await pumpApp(tester, prefs: _en, token: 't', catalog: _catalog());

    expect(find.text('Choose a service'), findsOneWidget);
    expect(find.byKey(const Key('family-1')), findsOneWidget);
    expect(find.byKey(const Key('family-2')), findsOneWidget);
    expect(find.text('Taxi'), findsOneWidget);
    expect(find.text('Economy car'), findsOneWidget);
    expect(find.text('Motorcycle courier'), findsNothing);
    expect(find.text('4 passengers'), findsWidgets);
    expect(find.text('2 bags'), findsOneWidget);
    expect(find.text('From EGP 15.00'), findsOneWidget);
    expect(find.text('Name your fare'), findsWidgets);
  });

  testWidgets('choosing a family filters the categories', (tester) async {
    await pumpApp(tester, prefs: _en, token: 't', catalog: _catalog());

    await tester.tap(find.byKey(const Key('family-2')));
    await tester.pumpAndSettle();
    expect(find.text('Motorcycle courier'), findsOneWidget);
    expect(find.text('Taxi'), findsNothing);
    expect(find.text('Up to 20 kg'), findsOneWidget);
    expect(find.text('Name your fare'), findsNothing);
  });

  testWidgets('Arabic is the default: Arabic names, Arabic digits, RTL', (
    tester,
  ) async {
    await pumpApp(tester, prefs: _ar, token: 't', catalog: _catalog());

    expect(find.text('اختر الخدمة'), findsOneWidget);
    expect(find.text('تاكسي'), findsWidgets);
    expect(find.textContaining('١٥٫٠٠'), findsOneWidget);
    expect(directionOf(tester, find.text('اختر الخدمة')), TextDirection.rtl);
  });

  testWidgets(
    'selecting a category highlights it and shows the details entry',
    (tester) async {
      final container = await pumpApp(
        tester,
        prefs: _en,
        token: 't',
        catalog: _catalog(),
      );
      expect(find.byKey(const Key('selected-service-panel')), findsNothing);

      await tester.tap(find.byKey(const Key('category-10')));
      await tester.pumpAndSettle();

      expect(container.read(selectedCategoryProvider)!.id, 10);
      expect(find.byKey(const Key('selected-service-panel')), findsOneWidget);
      expect(find.text('Vehicles: Taxi'), findsOneWidget);
      expect(find.byIcon(Icons.check_circle), findsOneWidget);

      await tester.tap(find.byKey(const Key('category-11')));
      await tester.pumpAndSettle();
      expect(container.read(selectedCategoryProvider)!.id, 11);
      expect(
        find.byKey(const Key('open-service-details')),
        findsNothing,
        reason: 'no form fields',
      );
    },
  );

  testWidgets('service details renders the schema, validates, and saves', (
    tester,
  ) async {
    final container = await pumpApp(
      tester,
      prefs: _en,
      token: 't',
      catalog: _catalog(),
    );
    await tester.tap(find.byKey(const Key('category-10')));
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byKey(const Key('open-service-details')));
    await tester.tap(find.byKey(const Key('open-service-details')));
    await tester.pumpAndSettle();

    expect(find.text('Service details'), findsWidgets);
    expect(find.text('Passengers *'), findsOneWidget);
    expect(find.text('Optional'), findsOneWidget);

    // Missing required field blocks saving and says why.
    await tester.tap(find.byKey(const Key('save-service-details')));
    await tester.pumpAndSettle();
    expect(find.text('This field is required'), findsOneWidget);
    expect(container.read(serviceDetailsProvider), isEmpty);

    await tester.enterText(find.byKey(const ValueKey('passengers-input')), '9');
    await tester.pump();
    expect(find.text('Must be at most 4'), findsOneWidget);

    await tester.enterText(find.byKey(const ValueKey('passengers-input')), '3');
    await tester.enterText(find.byKey(const ValueKey('notes-input')), 'Gate B');
    await tester.tap(find.byKey(const Key('save-service-details')));
    await tester.pump();
    for (var i = 0; i < 6; i++) {
      await tester.pump(const Duration(milliseconds: 150));
    }

    expect(container.read(serviceDetailsProvider), {
      10: {'passengers': 3, 'notes': 'Gate B'},
    });
    expect(find.text('Details saved'), findsOneWidget);
    await tester.pumpAndSettle();
    expect(
      find.byKey(const Key('selected-service-panel')),
      findsOneWidget,
      reason: 'back on home',
    );

    // Reopening restores the saved answers.
    await tester.ensureVisible(find.byKey(const Key('open-service-details')));
    await tester.tap(find.byKey(const Key('open-service-details')));
    await tester.pumpAndSettle();
    expect(find.text('3'), findsOneWidget);
  });

  testWidgets('empty catalog shows a clear empty state', (tester) async {
    await pumpApp(
      tester,
      prefs: _en,
      token: 't',
      catalog: FakeCatalogRepository(),
    );

    expect(find.text('No services available'), findsOneWidget);
    expect(find.byKey(const Key('category-10')), findsNothing);
  });

  testWidgets('a failing catalog shows retry and recovers', (tester) async {
    final catalog = _catalog()
      ..error = const ApiException(
        kind: ApiFailureKind.server,
        code: 'http_500',
        message: null,
      );
    await pumpApp(tester, prefs: _en, token: 't', catalog: catalog);

    expect(find.text('Something went wrong'), findsOneWidget);
    expect(find.text('Taxi'), findsNothing);

    catalog.error = null;
    await tester.tap(find.text('Try again'));
    await tester.pumpAndSettle();
    expect(find.text('Taxi'), findsOneWidget);
    expect(catalog.familiesCalls, 2);
  });

  testWidgets('offline is reported as offline, not a generic failure', (
    tester,
  ) async {
    final catalog = _catalog()
      ..error = const ApiException(
        kind: ApiFailureKind.network,
        code: 'offline',
        message: null,
      );
    await pumpApp(tester, prefs: _en, token: 't', catalog: catalog);

    expect(find.text('You are offline'), findsOneWidget);
  });
}
