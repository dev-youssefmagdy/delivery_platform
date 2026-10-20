import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../auth/presentation/providers/auth_providers.dart';

/// Entry point of the driver application flow. The driver app stops here after
/// phone verification: documents, vehicle and review open with P2.1, and this
/// screen says so instead of faking those steps. Language switch and sign-out
/// are real.
class ProviderOnboardingScreen extends ConsumerWidget {
  const ProviderOnboardingScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final phone = ref.watch(meProvider).value?.phone;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.providerOnboardingTitle)),
      body: ListView(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        children: [
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: StatusChip(
              label: l10n.providerOnboardingBadge,
              tone: ChipTone.info,
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            l10n.providerOnboardingHeadline,
            key: const Key('onboarding-coming'),
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(l10n.providerOnboardingBody),
          if (phone != null) ...[
            const SizedBox(height: AppSpacing.md),
            Text(
              l10n.providerOnboardingVerifiedPhone(phone),
              textDirection: TextDirection.ltr,
            ),
          ],
          const SizedBox(height: AppSpacing.lg),
          ListTile(
            minTileHeight: context.density.minTouchTarget,
            leading: const Icon(Icons.language),
            title: Text(l10n.homeChangeLanguage),
            onTap: () => context.go('${Routes.language}?from=settings'),
          ),
          ListTile(
            key: const Key('onboarding-logout'),
            minTileHeight: context.density.minTouchTarget,
            leading: const Icon(Icons.logout),
            title: Text(l10n.homeLogout),
            onTap: () => ref.read(authActionsProvider).logout(),
          ),
        ],
      ),
    );
  }
}
