import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/providers.dart';
import 'app.dart';

/// Initializes storage and the persisted locale BEFORE the first frame, then
/// runs the app inside a guarded error zone.
Future<void> bootstrap() async {
  await runZonedGuarded<Future<void>>(() async {
    WidgetsFlutterBinding.ensureInitialized();
    FlutterError.onError = (details) {
      FlutterError.presentError(details);
      // Crash reporting is wired in P6.4; never log PII or tokens here.
    };
    PlatformDispatcher.instance.onError = (error, stack) {
      debugPrint('Unhandled platform error: $error');
      return true;
    };

    final prefs = await SharedPreferences.getInstance();
    final container = ProviderContainer(
      overrides: [sharedPreferencesProvider.overrideWithValue(prefs)],
    );
    // Restore the session so the router's first redirect is correct.
    await container.read(sessionControllerProvider).restore();

    if (kDebugMode) {
      final env = container.read(envProvider);
      debugPrint('Flavor: ${env.flavor.name}, API: ${env.apiBaseUrl}');
    }

    runApp(UncontrolledProviderScope(container: container, child: const App()));
  }, (error, stack) => debugPrint('Uncaught zone error: $error'));
}
