import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../features/auth/presentation/providers/auth_providers.dart';
import '../features/jobs/presentation/screens/application_status_screen.dart';
import '../features/jobs/presentation/screens/cancel_job_screen.dart';
import '../features/jobs/presentation/screens/job_screen.dart';
import '../features/jobs/presentation/screens/report_job_screen.dart';
import '../features/auth/presentation/screens/account_status_screen.dart';
import '../features/auth/presentation/screens/auth_screen.dart';
import '../features/auth/presentation/screens/otp_screen.dart';
import '../features/language/presentation/screens/language_screen.dart';
import '../features/notifications/presentation/screens/notification_settings_screen.dart';
import '../features/notifications/presentation/screens/notifications_screen.dart';
import '../features/onboarding/presentation/screens/onboarding_screen.dart';
import '../features/provider_onboarding/presentation/screens/provider_onboarding_screen.dart';
import '../features/provider_onboarding/presentation/widgets/vehicle_editor_screen.dart';
import '../features/splash/presentation/screens/splash_screen.dart';

abstract final class Routes {
  static const splash = '/splash';
  static const language = '/language';
  static const onboarding = '/onboarding';
  static const auth = '/auth';
  static const otp = '/auth/otp';
  static const accountStatus = '/account-status';

  /// The driver application: wizard while it is being filled in, the
  /// verification status afterwards (P2.1).
  static const providerOnboarding = '/provider-onboarding';

  /// Vehicle editor (`/provider-onboarding/vehicle/:id`, or `.../new`).
  static const providerVehicle = '/provider-onboarding/vehicle';
  static const providerVehicleNew = '/provider-onboarding/vehicle/new';

  /// Application and document status, reachable from the driver home (P2.7).
  static const applicationStatus = '/application';

  /// A job: `/job/:id`, with `/job/:id/cancel` and `/job/:id/report`.
  static const job = '/job';
  static const notifications = '/notifications';
  static const notificationSettings = '/notifications/settings';
}

/// Where the splash screen sends the user.
String destinationAfterSplash({
  required SessionStatus session,
  required bool languageChosen,
  required bool onboardingDone,
}) {
  if (session == SessionStatus.authenticated) return Routes.providerOnboarding;
  if (!languageChosen) return Routes.language;
  if (!onboardingDone) return Routes.onboarding;
  return Routes.auth;
}

/// The screen that belongs to a signed-in [stage]. The driver app does not ask
/// for a profile or consents at sign-in: the application wizard collects them.
String routeForStage(AuthStage stage) => switch (stage) {
  AuthStage.signedOut => Routes.auth,
  AuthStage.loading => Routes.splash,
  AuthStage.error || AuthStage.accountStatus => Routes.accountStatus,
  AuthStage.needsProfile ||
  AuthStage.needsConsent ||
  AuthStage.ready => Routes.providerOnboarding,
};

bool _isJobPage(String location) =>
    location == Routes.applicationStatus ||
    location == Routes.notifications ||
    location == Routes.notificationSettings ||
    location == Routes.job ||
    location.startsWith('${Routes.job}/');

bool _isSignedInOnly(String location) =>
    location == Routes.accountStatus ||
    location == Routes.providerOnboarding ||
    location.startsWith('${Routes.providerOnboarding}/') ||
    _isJobPage(location);

const _signedOutOnly = {Routes.auth, Routes.otp, Routes.onboarding};

/// Pure redirect rules (unit tested). Returns null to stay.
String? computeRedirect({required String location, required AuthStage stage}) {
  if (stage == AuthStage.signedOut) {
    return _isSignedInOnly(location) ? Routes.splash : null;
  }
  // Sub-pages of the application (vehicle editor ...) stay reachable while the
  // stage leads to the onboarding hub.
  if (location.startsWith('${Routes.providerOnboarding}/') &&
      routeForStage(stage) == Routes.providerOnboarding) {
    return null;
  }
  // Job pages are reachable once the driver is in the app.
  if (_isJobPage(location) &&
      routeForStage(stage) == Routes.providerOnboarding) {
    return null;
  }
  final guarded =
      _isSignedInOnly(location) || _signedOutOnly.contains(location);
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
      GoRoute(
        path: Routes.accountStatus,
        builder: (_, _) => const AccountStatusScreen(),
      ),
      GoRoute(
        path: Routes.providerOnboarding,
        builder: (_, _) => const ProviderOnboardingScreen(),
      ),
      GoRoute(
        path: Routes.notifications,
        builder: (_, _) => const NotificationsScreen(),
      ),
      GoRoute(
        path: Routes.notificationSettings,
        builder: (_, _) => const NotificationSettingsScreen(),
      ),
      GoRoute(
        path: Routes.applicationStatus,
        builder: (_, _) => const ApplicationStatusScreen(),
      ),
      GoRoute(
        path: '${Routes.job}/:id',
        builder: (_, state) =>
            JobScreen(bookingId: state.pathParameters['id']!),
        routes: [
          GoRoute(
            path: 'cancel',
            builder: (_, state) =>
                CancelJobScreen(bookingId: state.pathParameters['id']!),
          ),
          GoRoute(
            path: 'report',
            builder: (_, state) =>
                ReportJobScreen(bookingId: state.pathParameters['id']!),
          ),
        ],
      ),
      GoRoute(
        path: Routes.providerVehicleNew,
        builder: (_, _) => const VehicleEditorScreen(),
      ),
      GoRoute(
        path: '${Routes.providerVehicle}/:id',
        builder: (_, state) =>
            VehicleEditorScreen(vehicleId: state.pathParameters['id']),
      ),
    ],
  );
  ref.onDispose(router.dispose);
  return router;
});
