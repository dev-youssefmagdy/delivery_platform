import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../providers/provider_onboarding_providers.dart';
import '../widgets/onboarding_labels.dart';
import '../widgets/onboarding_wizard.dart';
import '../widgets/verification_status_view.dart';

/// The driver application: the wizard while the application is being filled
/// in, the verification status once it is with the review team, approved or
/// suspended. The server's status decides, so a driver resumes on any device.
class ProviderOnboardingScreen extends ConsumerWidget {
  const ProviderOnboardingScreen({super.key});

  static bool _isFilling(ProviderApplication app) =>
      !app.started ||
      const {
        ProviderStatus.draft,
        ProviderStatus.phoneVerified,
        ProviderStatus.profileSubmitted,
        ProviderStatus.documentsPending,
      }.contains(app.status);

  Future<void> _refresh(BuildContext context, WidgetRef ref) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final error = await ref.read(applicationProvider.notifier).reload();
    if (error != null) {
      messenger.showSnackBar(
        SnackBar(content: Text(onboardingErrorText(l10n, error))),
      );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final application = ref.watch(applicationProvider);
    final wizardOpen = ref.watch(wizardOpenProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.providerOnboardingTitle),
        actions: [
          IconButton(
            key: const Key('onboarding-refresh'),
            tooltip: l10n.actionRefresh,
            icon: const Icon(Icons.refresh),
            onPressed: () => _refresh(context, ref),
          ),
          PopupMenuButton<String>(
            key: const Key('onboarding-menu'),
            onSelected: (value) {
              if (value == 'language') {
                context.go('${Routes.language}?from=settings');
              } else if (value == 'logout') {
                ref.read(authActionsProvider).logout();
              }
            },
            itemBuilder: (_) => [
              PopupMenuItem(
                value: 'language',
                child: Text(l10n.homeChangeLanguage),
              ),
              PopupMenuItem(
                key: const Key('onboarding-logout'),
                value: 'logout',
                child: Text(l10n.homeLogout),
              ),
            ],
          ),
        ],
      ),
      body: SafeArea(
        child: application.when(
          skipLoadingOnReload: true,
          loading: () => LoadingView(semanticLabel: l10n.loadingLabel),
          error: (e, _) => ErrorState(
            offline: e is ApiException && e.isOffline,
            title: e is ApiException && e.isOffline
                ? l10n.errorOfflineTitle
                : l10n.errorGenericTitle,
            message: onboardingErrorText(l10n, e),
            retryLabel: l10n.actionRetry,
            onRetry: () => ref.read(applicationProvider.notifier).reload(),
          ),
          data: (app) => _isFilling(app) || wizardOpen
              ? OnboardingWizard(
                  key: ValueKey('wizard-${app.started}'),
                  application: app,
                  onFinished: () =>
                      ref.read(wizardOpenProvider.notifier).close(),
                )
              : RefreshIndicator(
                  onRefresh: () =>
                      ref.read(applicationProvider.notifier).reload(),
                  child: VerificationStatusView(
                    application: app,
                    onContinue: () =>
                        ref.read(wizardOpenProvider.notifier).open(),
                  ),
                ),
        ),
      ),
    );
  }
}
