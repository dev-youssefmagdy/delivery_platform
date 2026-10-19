import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../core/providers.dart';
import '../features/auth/presentation/screens/auth_screen.dart';
import '../features/home/presentation/screens/home_screen.dart';
import '../features/language/presentation/screens/language_screen.dart';
import '../features/onboarding/presentation/screens/onboarding_screen.dart';
import '../features/splash/presentation/screens/splash_screen.dart';

abstract final class Routes {
  static const splash = '/splash';
  static const language = '/language';
  static const onboarding = '/onboarding';
  static const auth = '/auth';
  static const home = '/home';
}

/// Where the splash screen sends the user.
String destinationAfterSplash({
  required SessionStatus session,
  required bool languageChosen,
  required bool onboardingDone,
}) {
  if (session == SessionStatus.authenticated) return Routes.home;
  if (!languageChosen) return Routes.language;
  if (!onboardingDone) return Routes.onboarding;
  return Routes.auth;
}

/// Pure redirect rules (unit tested). Returns null to stay.
String? computeRedirect({
  required String location,
  required SessionStatus session,
}) {
  final authenticated = session == SessionStatus.authenticated;
  if (authenticated &&
      (location == Routes.auth || location == Routes.onboarding)) {
    return Routes.home;
  }
  if (!authenticated && location == Routes.home) return Routes.splash;
  return null;
}

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.listen(sessionStatusProvider, (_, _) => refresh.value++);
  ref.onDispose(refresh.dispose);

  final router = GoRouter(
    initialLocation: Routes.splash,
    refreshListenable: refresh,
    redirect: (context, state) => computeRedirect(
      location: state.uri.path,
      session: ref.read(sessionStatusProvider),
    ),
    routes: [
      GoRoute(path: Routes.splash, builder: (_, _) => const SplashScreen()),
      GoRoute(
        path: Routes.language,
        builder: (_, state) => LanguageScreen(
          fromSettings: state.uri.queryParameters['from'] == 'settings',
        ),
      ),
      GoRoute(
        path: Routes.onboarding,
        builder: (_, _) => const OnboardingScreen(),
      ),
      GoRoute(path: Routes.auth, builder: (_, _) => const AuthScreen()),
      GoRoute(path: Routes.home, builder: (_, _) => const HomeScreen()),
    ],
  );
  ref.onDispose(router.dispose);
  return router;
});
