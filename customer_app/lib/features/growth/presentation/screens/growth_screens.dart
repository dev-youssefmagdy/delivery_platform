import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../providers/growth_providers.dart';

/// Shown when a growth screen is reached (deep link) while its server flag is off.
class _Unavailable extends StatelessWidget {
  const _Unavailable({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: EmptyState(
        key: const Key('growth-unavailable'),
        icon: Icons.block,
        title: l10n.growUnavailable,
      ),
    );
  }
}

/// Referral share screen: the customer's code and link, the offer, the code
/// entry for newcomers and the progress. Only reachable while `referrals` is on.
class ReferralScreen extends ConsumerWidget {
  const ReferralScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    if (!ref.watch(growthFeatureProvider(GrowthFlags.referrals))) {
      return _Unavailable(title: l10n.refTitle);
    }
    final overview = ref.watch(referralProvider);
    final notifier = ref.read(referralProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.refTitle)),
      body: SafeArea(
        child: AsyncStateView<ReferralOverview>(
          value: overview,
          onRetry: notifier.reload,
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: ReferralShareView(
              overview: data,
              formatter: ref.watch(moneyFormatterProvider),
              strings: referralStrings(l10n),
              onApply: notifier.apply,
            ),
          ),
        ),
      ),
    );
  }
}

/// Promotional credits (referral and loyalty rewards) with their expiry.
class CreditsScreen extends ConsumerWidget {
  const CreditsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    if (!ref.watch(growthFeatureProvider(GrowthFlags.referrals))) {
      return _Unavailable(title: l10n.credTitle);
    }
    final credits = ref.watch(creditsProvider);
    final notifier = ref.read(creditsProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.credTitle)),
      body: SafeArea(
        child: AsyncStateView<CreditsOverview>(
          value: credits,
          onRetry: notifier.reload,
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: CreditsView(
              overview: data,
              formatter: ref.watch(moneyFormatterProvider),
              strings: creditsStrings(l10n),
            ),
          ),
        ),
      ),
    );
  }
}

/// Loyalty status: level, progress and the ladder of levels. Only while `loyalty` is on.
class LoyaltyScreen extends ConsumerWidget {
  const LoyaltyScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    if (!ref.watch(growthFeatureProvider(GrowthFlags.loyalty))) {
      return _Unavailable(title: l10n.loyTitle);
    }
    final status = ref.watch(loyaltyProvider);
    final notifier = ref.read(loyaltyProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.loyTitle)),
      body: SafeArea(
        child: AsyncStateView<LoyaltyStatus>(
          value: status,
          onRetry: notifier.reload,
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: LoyaltyView(
              status: data,
              formatter: ref.watch(moneyFormatterProvider),
              strings: loyaltyStrings(l10n),
            ),
          ),
        ),
      ),
    );
  }
}
