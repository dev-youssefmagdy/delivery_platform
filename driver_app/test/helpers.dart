import 'package:driver_app/app/app.dart';
import 'package:driver_app/core/providers.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Pumps the whole app with in-memory storage and no splash delay.
Future<ProviderContainer> pumpApp(
  WidgetTester tester, {
  Map<String, Object> prefs = const {},
  String? token,
}) async {
  SharedPreferences.setMockInitialValues(prefs);
  final sp = await SharedPreferences.getInstance();
  final store = MemoryTokenStore(token);
  final container = ProviderContainer(
    overrides: [
      sharedPreferencesProvider.overrideWithValue(sp),
      tokenStoreProvider.overrideWithValue(store),
      splashDurationProvider.overrideWithValue(Duration.zero),
      onlineProvider.overrideWith((ref) => Stream.value(true)),
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
