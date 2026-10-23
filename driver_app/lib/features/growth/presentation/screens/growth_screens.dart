import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
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
      body: EmptyState(key: const Key('growth-unavailable'), icon: Icons.block, title: l10n.growUnavailable),
    );
  }
}

/// Provider referrals: the driver's code and link, the offer and the progress of
/// invited drivers. Only reachable while `referrals` is on.
class ReferralScreen extends ConsumerWidget {
  const ReferralScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    if (!ref.watch(growthFeatureProvider(GrowthFlags.referrals))) return _Unavailable(title: l10n.refTitle);
    final overview = ref.watch(referralProvider);
    final notifier = ref.read(referralProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.refTitle)),
      body: SafeArea(
        child: AsyncStateView<ReferralOverview>(
          value: overview,
          onRetry: notifier.reload,
          onSignIn: () => ref.read(authActionsProvider).logout(),
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

/// Incentive campaigns with quest progress. Only reachable while `incentives` is on.
class IncentivesScreen extends ConsumerWidget {
  const IncentivesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    if (!ref.watch(growthFeatureProvider(GrowthFlags.incentives))) return _Unavailable(title: l10n.incTitle);
    final quests = ref.watch(incentivesProvider);
    final notifier = ref.read(incentivesProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.incTitle)),
      body: SafeArea(
        child: AsyncStateView<List<IncentiveQuest>>(
          value: quests,
          onRetry: notifier.reload,
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, data) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: IncentivesView(quests: data, formatter: ref.watch(moneyFormatterProvider), strings: incentiveStrings(l10n)),
          ),
        ),
      ),
    );
  }
}
