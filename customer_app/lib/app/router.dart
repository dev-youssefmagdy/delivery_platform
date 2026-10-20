import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../features/auth/presentation/providers/auth_providers.dart';
import '../features/auth/presentation/screens/account_status_screen.dart';
import '../features/auth/presentation/screens/auth_screen.dart';
import '../features/auth/presentation/screens/consent_screen.dart';
import '../features/auth/presentation/screens/otp_screen.dart';
import '../features/auth/presentation/screens/profile_screen.dart';
import '../features/home/presentation/screens/home_screen.dart';
import '../features/language/presentation/screens/language_screen.dart';
import '../features/onboarding/presentation/screens/onboarding_screen.dart';
import '../features/splash/presentation/screens/splash_screen.dart';

abstract final class Routes {
  static const splash = '/splash';
  static const language = '/language';
  static const onboarding = '/onboarding';
  static const auth = '/auth';
  static const otp = '/auth/otp';
  static const profile = '/profile';
  static const consent = '/consent';
  static const accountStatus = '/account-status';
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

/// The screen that belongs to a signed-in [stage].
String routeForStage(AuthStage stage) => switch (stage) {
  AuthStage.signedOut => Routes.auth,
  AuthStage.loading => Routes.splash,
  AuthStage.error || AuthStage.accountStatus => Routes.accountStatus,
  AuthStage.needsProfile => Routes.profile,
  AuthStage.needsConsent => Routes.consent,
  AuthStage.ready => Routes.home,
};

const _signedInOnly = {
  Routes.home,
  Routes.profile,
  Routes.consent,
  Routes.accountStatus,
};

const _signedOutOnly = {Routes.auth, Routes.otp, Routes.onboarding};

/// Pure redirect rules (unit tested). Returns null to stay.
String? computeRedirect({required String location, required AuthStage stage}) {
  if (stage == AuthStage.signedOut) {
    return _signedInOnly.contains(location) ? Routes.splash : null;
  }
  final guarded =
      _signedInOnly.contains(location) || _signedOutOnly.contains(location);
  if (!guarded) return null;
  final target = routeForStage(stage);
  return target == location ? null : target;
}

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.listen(authStageProvider, (_, _) => refresh.value++);
  ref.onDispose(refresh.dispose);

  final router = GoRouter(
    initialLocation: Routes.splash,
    refreshListenable: refresh,
    redirect: (context, state) => computeRedirect(
      location: state.uri.path,
      stage: ref.read(authStageProvider),
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
      GoRoute(path: Routes.otp, builder: (_, _) => const OtpScreen()),
      GoRoute(path: Routes.profile, builder: (_, _) => const ProfileScreen()),
      GoRoute(path: Routes.consent, builder: (_, _) => const ConsentScreen()),
      GoRoute(
        path: Routes.accountStatus,
        builder: (_, _) => const AccountStatusScreen(),
      ),
      GoRoute(path: Routes.home, builder: (_, _) => const HomeScreen()),
    ],
  );
  ref.onDispose(router.dispose);
  return router;
});
