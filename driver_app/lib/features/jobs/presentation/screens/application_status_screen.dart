import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/async_state_view.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../provider_onboarding/presentation/providers/provider_onboarding_providers.dart';
import '../../../provider_onboarding/presentation/widgets/verification_status_view.dart';

/// The application and document status reachable from the driver home.
class ApplicationStatusScreen extends ConsumerWidget {
  const ApplicationStatusScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final application = ref.watch(applicationProvider);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.providerOnboardingTitle)),
      body: SafeArea(
        child: AsyncStateView<ProviderApplication>(
          value: application,
          onRetry: () => ref.read(applicationProvider.notifier).reload(),
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, app) => VerificationStatusView(
            application: app,
            onContinue: () {
              ref.read(wizardOpenProvider.notifier).open();
              context.pop();
            },
          ),
        ),
      ),
    );
  }
}
