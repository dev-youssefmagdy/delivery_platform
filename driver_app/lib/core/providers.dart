import 'dart:async';

import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../features/app_config/presentation/providers/app_config_providers.dart';

/// Overridden in bootstrap with the preloaded instance.
final sharedPreferencesProvider = Provider<SharedPreferences>(
  (ref) => throw UnimplementedError('sharedPreferencesProvider not overridden'),
);

final envProvider = Provider<Env>((ref) => Env.fromEnvironment());

final tokenStoreProvider = Provider<TokenStore>((ref) => SecureTokenStore());

final localePreferenceProvider = Provider<LocalePreference>(
  (ref) => LocalePreference(ref.watch(sharedPreferencesProvider)),
);

/// Session holder; bootstrap calls `restore()` before the first frame.
final sessionControllerProvider = Provider<SessionController>((ref) {
  final controller = SessionController(ref.watch(tokenStoreProvider));
  ref.onDispose(controller.dispose);
  return controller;
});

class SessionNotifier extends Notifier<SessionStatus> {
  @override
  SessionStatus build() {
    final controller = ref.watch(sessionControllerProvider);
    final sub = controller.changes.listen((s) => state = s);
    ref.onDispose(sub.cancel);
    return controller.status;
  }
}

final sessionStatusProvider = NotifierProvider<SessionNotifier, SessionStatus>(
  SessionNotifier.new,
);

/// The user's chosen language; until they choose one, the server's default
/// language (`default_locale`), falling back to Arabic.
class LocaleNotifier extends Notifier<Locale> {
  @override
  Locale build() {
    final preference = ref.watch(localePreferenceProvider);
    final stored = preference.stored;
    if (stored != null) return Locale(stored);
    final serverDefault = ref
        .watch(appConfigProvider)
        .value
        ?.defaultLanguageCode;
    return Locale(serverDefault ?? defaultLanguageCode);
  }

  Future<void> setLanguage(String languageCode) async {
    await ref.read(localePreferenceProvider).save(languageCode);
    state = Locale(languageCode);
  }
}

final localeProvider = NotifierProvider<LocaleNotifier, Locale>(
  LocaleNotifier.new,
);

/// True once the user has explicitly picked a language.
final languageChosenProvider = Provider<bool>((ref) {
  ref.watch(localeProvider);
  return ref.watch(localePreferenceProvider).stored != null;
});

const _onboardingKey = 'onboarding_done';

class OnboardingNotifier extends Notifier<bool> {
  @override
  bool build() =>
      ref.watch(sharedPreferencesProvider).getBool(_onboardingKey) ?? false;

  Future<void> complete() async {
    await ref.read(sharedPreferencesProvider).setBool(_onboardingKey, true);
    state = true;
  }
}

final onboardingDoneProvider = NotifierProvider<OnboardingNotifier, bool>(
  OnboardingNotifier.new,
);

final apiClientProvider = Provider<ApiClient>((ref) {
  final session = ref.watch(sessionControllerProvider);
  final client = ApiClient(
    env: ref.watch(envProvider),
    readToken: session.readToken,
    readLanguageCode: () => ref.read(localeProvider).languageCode,
  );
  final sub = client.sessionExpired.listen((_) => session.markExpired());
  ref.onDispose(() {
    sub.cancel();
    client.dispose();
  });
  return client;
});

/// Connectivity (transport presence). True while online.
final onlineProvider = StreamProvider<bool>((ref) => onlineStream());

/// How long the splash waits for the startup config before continuing without it.
final configWaitProvider = Provider<Duration>(
  (ref) => const Duration(seconds: 4),
);

/// Splash display time; tests override with `Duration.zero`.
final splashDurationProvider = Provider<Duration>(
  (ref) => const Duration(milliseconds: 900),
);

/// Background polling of jobs, chats, earnings adjustments and the proof upload queue (realtime is the primary channel). Tests switch it off.
final pollingEnabledProvider = Provider<bool>((ref) => true);
