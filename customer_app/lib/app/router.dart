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
import '../features/booking/presentation/screens/booking_form_screen.dart';
import '../features/booking/presentation/screens/booking_screen.dart';
import '../features/booking/presentation/screens/cancel_booking_screen.dart';
import '../features/booking/presentation/screens/report_screen.dart';
import '../features/support/presentation/screens/appeal_screen.dart';
import '../features/support/presentation/screens/chat_screen.dart';
import '../features/support/presentation/screens/new_ticket_screen.dart';
import '../features/support/presentation/screens/ticket_detail_screen.dart';
import '../features/support/presentation/screens/tickets_screen.dart';
import '../features/catalog/presentation/screens/service_details_screen.dart';
import '../features/home/presentation/screens/home_screen.dart';
import '../features/map/presentation/providers/map_providers.dart';
import '../features/map/presentation/screens/map_screen_args.dart';
import '../features/map/presentation/screens/pin_adjust_screen.dart';
import '../features/map/presentation/screens/place_search_screen.dart';
import '../features/map/presentation/screens/saved_places_screen.dart';
import '../features/language/presentation/screens/language_screen.dart';
import '../features/notifications/presentation/screens/notification_settings_screen.dart';
import '../features/notifications/presentation/screens/notifications_screen.dart';
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
  static const serviceDetails = '/service-details';
  static const placeSearch = '/place-search';
  static const pinAdjust = '/pin-adjust';
  static const savedPlaces = '/saved-places';
  static const bookingNew = '/booking/new';
  static const notifications = '/notifications';
  static const notificationSettings = '/notifications/settings';
  static const support = '/support';
  static const supportNew = '/support/new';

  /// A support request (also the target of reply notifications).
  static String supportTicket(String id) => '/support/ticket/$id';

  /// A booking at any stage; also the target of deep links and notification taps.
  static String booking(String id) => '/booking/$id';
  static String bookingCancel(String id) => '/booking/$id/cancel';
  static String bookingReport(String id) => '/booking/$id/report';
  static String bookingChat(String id) => '/booking/$id/chat';
  static String bookingAppeal(String id) => '/booking/$id/appeal';
  static String bookingSupport(String id, String code) => '/support/new?booking=$id&code=$code';
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

/// Screens pushed on top of home: only for a fully set-up account.
const _readyOnly = {
  Routes.serviceDetails,
  Routes.placeSearch,
  Routes.pinAdjust,
  Routes.savedPlaces,
  Routes.notifications,
  Routes.notificationSettings,
};

/// Pure redirect rules (unit tested). Returns null to stay.
String? computeRedirect({required String location, required AuthStage stage}) {
  if (_readyOnly.contains(location) || location.startsWith('/booking/')) {
    if (stage == AuthStage.ready) return null;
    return stage == AuthStage.signedOut ? Routes.splash : routeForStage(stage);
  }
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
      GoRoute(
        path: Routes.serviceDetails,
        builder: (_, _) => const ServiceDetailsScreen(),
      ),
      GoRoute(
        path: Routes.placeSearch,
        builder: (_, state) => PlaceSearchScreen(
          args:
              state.extra as PlaceSearchArgs? ??
              const PlaceSearchArgs(mode: PlaceSearchMode.destination),
        ),
      ),
      GoRoute(
        path: Routes.pinAdjust,
        builder: (_, state) => PinAdjustScreen(
          args:
              state.extra as PinAdjustArgs? ??
              const PinAdjustArgs(initial: defaultMapCenter),
        ),
      ),
      GoRoute(
        path: Routes.savedPlaces,
        builder: (_, _) => const SavedPlacesScreen(),
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
        path: Routes.support,
        builder: (_, _) => const TicketsScreen(),
        routes: [
          GoRoute(
            path: 'new',
            builder: (_, state) => NewTicketScreen(
              bookingId: state.uri.queryParameters['booking'],
              bookingCode: state.uri.queryParameters['code'],
            ),
          ),
          GoRoute(
            path: 'ticket/:id',
            builder: (_, state) =>
                TicketDetailScreen(ticketId: state.pathParameters['id']!),
          ),
        ],
      ),
      GoRoute(
        path: Routes.bookingNew,
        builder: (_, _) => const BookingFormScreen(),
      ),
      GoRoute(
        path: '/booking/:id',
        builder: (_, state) =>
            BookingScreen(bookingId: state.pathParameters['id']!),
        routes: [
          GoRoute(
            path: 'cancel',
            builder: (_, state) =>
                CancelBookingScreen(bookingId: state.pathParameters['id']!),
          ),
          GoRoute(
            path: 'report',
            builder: (_, state) =>
                ReportScreen(bookingId: state.pathParameters['id']!),
          ),
          GoRoute(
            path: 'chat',
            builder: (_, state) =>
                ChatScreen(bookingId: state.pathParameters['id']!),
          ),
          GoRoute(
            path: 'appeal',
            builder: (_, state) =>
                AppealScreen(bookingId: state.pathParameters['id']!),
          ),
        ],
      ),
    ],
  );
  ref.onDispose(router.dispose);
  return router;
});
