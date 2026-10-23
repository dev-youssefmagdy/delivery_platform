import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../features/auth/presentation/providers/auth_providers.dart';
import '../features/earnings/presentation/screens/earning_lines_screen.dart';
import '../features/earnings/presentation/screens/payout_detail_screen.dart';
import '../features/earnings/presentation/screens/payout_method_form_screen.dart';
import '../features/earnings/presentation/screens/payout_methods_screen.dart';
import '../features/earnings/presentation/screens/payout_request_screen.dart';
import '../features/earnings/presentation/screens/payouts_screen.dart';
import '../features/earnings/presentation/screens/wallet_ledger_screen.dart';
import '../features/earnings/presentation/screens/job_receipt_screen.dart';
import '../features/jobs/presentation/screens/application_status_screen.dart';
import '../features/jobs/presentation/screens/cancel_job_screen.dart';
import '../features/jobs/presentation/screens/job_screen.dart';
import '../features/jobs/presentation/screens/report_job_screen.dart';
import '../features/safety/presentation/screens/safety_screens.dart';
import '../features/support/presentation/screens/appeal_screen.dart';
import '../features/support/presentation/screens/chat_screen.dart';
import '../features/support/presentation/screens/new_ticket_screen.dart';
import '../features/support/presentation/screens/ticket_detail_screen.dart';
import '../features/support/presentation/screens/tickets_screen.dart';
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
  static const support = '/support';
  static const safetyIncidents = '/safety/incidents';
  static const safetyContacts = '/safety/contacts';
  static String safetyIncident(String id) => '/safety/incidents/$id';
  static String safetyReport(String bookingId) => '/safety/report/$bookingId';
  static const supportNew = '/support/new';
  static const earningLines = '/earnings/lines';

  /// The wallet ledger (also the target of wallet notifications).
  static const walletLedger = '/wallet';
  static const payouts = '/payouts';
  static const payoutRequest = '/payouts/new';
  static const payoutMethods = '/payout-methods';
  static const payoutMethodNew = '/payout-methods/edit';
  static String payout(String id) => '/payouts/$id';
  static String jobReceipt(String id) => '/job/$id/receipt';
  static String payoutSupport(String id) => '/support/new?payout=$id';

  /// A support request (also the target of reply notifications).
  static String supportTicket(String id) => '/support/ticket/$id';
  static String jobChat(String id) => '/job/$id/chat';
  static String jobAppeal(String id) => '/job/$id/appeal';
  static String jobSupport(String id, String code) => '/support/new?booking=$id&code=$code';
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
    location == Routes.earningLines ||
    location == Routes.walletLedger ||
    location == Routes.payouts ||
    location.startsWith('${Routes.payouts}/') ||
    location.startsWith(Routes.payoutMethods) ||
    location == Routes.job ||
    location.startsWith('${Routes.job}/') ||
    location == Routes.support ||
    location.startsWith('${Routes.support}/') ||
    location.startsWith('/safety/');

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
      GoRoute(path: Routes.safetyContacts, builder: (_, _) => const SafetyContactsScreen()),
      GoRoute(
        path: Routes.safetyIncidents,
        builder: (_, _) => const SafetyIncidentsScreen(),
        routes: [GoRoute(path: ':id', builder: (_, state) => SafetyIncidentScreen(incidentId: state.pathParameters['id']!))],
      ),
      GoRoute(path: '/safety/report/:bookingId', builder: (_, state) => SafetyReportScreen(bookingId: state.pathParameters['bookingId']!)),
      GoRoute(
        path: Routes.support,
        builder: (_, _) => const TicketsScreen(),
        routes: [
          GoRoute(
            path: 'new',
            builder: (_, state) => NewTicketScreen(
              bookingId: state.uri.queryParameters['booking'],
              bookingCode: state.uri.queryParameters['code'],
              paymentId: state.uri.queryParameters['payment'],
              payoutId: state.uri.queryParameters['payout'],
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
        path: Routes.applicationStatus,
        builder: (_, _) => const ApplicationStatusScreen(),
      ),
      GoRoute(path: Routes.earningLines, builder: (_, _) => const EarningLinesScreen()),
      GoRoute(path: Routes.walletLedger, builder: (_, _) => const WalletLedgerScreen()),
      GoRoute(path: Routes.payouts, builder: (_, _) => const PayoutsScreen()),
      GoRoute(path: Routes.payoutRequest, builder: (_, _) => const PayoutRequestScreen()),
      GoRoute(path: '${Routes.payouts}/:id', builder: (_, state) => PayoutDetailScreen(payoutId: state.pathParameters['id']!)),
      GoRoute(path: Routes.payoutMethods, builder: (_, _) => const PayoutMethodsScreen()),
      GoRoute(path: Routes.payoutMethodNew, builder: (_, state) => PayoutMethodFormScreen(editing: state.extra as PayoutMethod?)),
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
          GoRoute(
            path: 'chat',
            builder: (_, state) =>
                ChatScreen(bookingId: state.pathParameters['id']!),
          ),
          GoRoute(
            path: 'receipt',
            builder: (_, state) => JobReceiptScreen(bookingId: state.pathParameters['id']!),
          ),
          GoRoute(
            path: 'appeal',
            builder: (_, state) =>
                AppealScreen(bookingId: state.pathParameters['id']!),
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
