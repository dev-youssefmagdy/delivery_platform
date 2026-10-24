import 'package:driver_app/app/app.dart';
import 'package:driver_app/core/providers.dart';
import 'package:driver_app/features/app_config/domain/entities/app_config.dart';
import 'package:driver_app/features/app_config/presentation/providers/app_config_providers.dart';
import 'package:driver_app/features/auth/presentation/providers/auth_providers.dart';
import 'package:driver_app/features/availability/presentation/providers/availability_providers.dart';
import 'package:driver_app/features/provider_onboarding/presentation/providers/provider_onboarding_providers.dart';
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
  FakeProviderRepository? provider,
  FakeCatalogRepository? catalog,
  FakePhotoHandler? photos,
  FakeAvailabilityRepository? availability,
  FakeLocationService? location,
  FakeTrackingSource? tracking,
  FakeRealtimeClient? realtime,
}) async {
  SharedPreferences.setMockInitialValues(prefs);
  final sp = await SharedPreferences.getInstance();
  final store = MemoryTokenStore(token);
  final container = ProviderContainer(
    overrides: [
      sharedPreferencesProvider.overrideWithValue(sp),
      tokenStoreProvider.overrideWithValue(store),
      splashDurationProvider.overrideWithValue(Duration.zero),
      pollingEnabledProvider.overrideWithValue(false),
      onlineProvider.overrideWith((ref) => Stream.value(true)),
      appConfigProvider.overrideWith(
        (ref) => configBuilder != null ? configBuilder() : Future.value(config),
      ),
      appVersionProvider.overrideWith((ref) async => version),
      identityRepositoryProvider.overrideWithValue(
        identity ?? FakeIdentityRepository(),
      ),
      providerRepositoryProvider.overrideWithValue(
        provider ?? FakeProviderRepository(),
      ),
      catalogRepositoryProvider.overrideWithValue(
        catalog ?? FakeCatalogRepository(),
      ),
      availabilityRepositoryProvider.overrideWithValue(
        availability ?? FakeAvailabilityRepository(),
      ),
      locationServiceProvider.overrideWithValue(
        location ?? FakeLocationService(),
      ),
      trackingSourceProvider.overrideWithValue(
        tracking ?? FakeTrackingSource(),
      ),
      realtimeClientProvider.overrideWithValue(
        realtime ?? FakeRealtimeClient(),
      ),
      photoPickerFactoryProvider.overrideWithValue(
        ({required cameraLabel, required galleryLabel}) =>
            photos ?? FakePhotoHandler('/tmp/fake-photo.jpg'),
      ),
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
