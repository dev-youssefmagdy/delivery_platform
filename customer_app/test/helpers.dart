import 'package:customer_app/app/app.dart';
import 'package:customer_app/core/providers.dart';
import 'package:customer_app/features/app_config/domain/entities/app_config.dart';
import 'package:customer_app/features/app_config/presentation/providers/app_config_providers.dart';
import 'package:customer_app/features/auth/presentation/providers/auth_providers.dart';
import 'package:customer_app/features/catalog/presentation/providers/catalog_providers.dart';
import 'package:customer_app/features/map/presentation/providers/map_providers.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'fakes.dart';

/// Pumps the whole app with in-memory storage and no splash delay.
Future<ProviderContainer> pumpApp(
  WidgetTester tester, {
  Map<String, Object> prefs = const {},
  String? token,
  AppConfig config = const AppConfig(
    defaultLanguageCode: 'ar',
    languageCodes: ['ar', 'en'],
    countryCode: 'EG',
    phoneCode: '+20',
    currencyCode: 'EGP',
    currencyExponent: 2,
    digitStyle: null,
    support: SupportContacts(),
    maintenance: false,
  ),
  String version = '1.0.0',
  Future<AppConfig> Function()? configBuilder,
  FakeIdentityRepository? identity,
  FakeCatalogRepository? catalog,
  FakeGeoRepository? geo,
  FakeLocationService? location,
}) async {
  // Tall surface: the home screen now starts with the map and trip fields.
  tester.view.physicalSize = const Size(800, 2600);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  SharedPreferences.setMockInitialValues(prefs);
  final sp = await SharedPreferences.getInstance();
  final store = MemoryTokenStore(token);
  final container = ProviderContainer(
    overrides: [
      sharedPreferencesProvider.overrideWithValue(sp),
      tokenStoreProvider.overrideWithValue(store),
      splashDurationProvider.overrideWithValue(Duration.zero),
      onlineProvider.overrideWith((ref) => Stream.value(true)),
      appConfigProvider.overrideWith(
        (ref) => configBuilder != null ? configBuilder() : Future.value(config),
      ),
      appVersionProvider.overrideWith((ref) async => version),
      identityRepositoryProvider.overrideWithValue(
        identity ?? FakeIdentityRepository(),
      ),
      catalogRepositoryProvider.overrideWithValue(
        catalog ?? FakeCatalogRepository(),
      ),
      geoRepositoryProvider.overrideWithValue(geo ?? FakeGeoRepository()),
      locationServiceProvider.overrideWithValue(
        location ?? FakeLocationService(),
      ),
      mapTileProviderProvider.overrideWithValue(NoNetworkTileProvider()),
    ],
  );
  addTearDown(container.dispose);
  await container.read(sessionControllerProvider).restore();
  await tester.pumpWidget(
    UncontrolledProviderScope(container: container, child: const App()),
  );
  await tester.pumpAndSettle();
  return container;
}

TextDirection directionOf(WidgetTester tester, Finder f) =>
    Directionality.of(tester.element(f));
