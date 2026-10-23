import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../growth/presentation/providers/growth_providers.dart';
import '../providers/earnings_providers.dart';
import 'cash_debt_banner.dart';

/// The Earnings tab: totals for a day, week or month, the split by service
/// category, a daily chart, the wallet position and the cash-debt banner, with
/// links to the booking-level breakdown, the wallet ledger and payouts.
/// Incentive and referral entries appear only while the server flags are on (P5.7).
class EarningsTab extends ConsumerWidget {
  const EarningsTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final summary = ref.watch(earningsSummaryProvider);
    final period = ref.watch(earningsPeriodProvider);
    final l10n = AppLocalizations.of(context);
    return AsyncStateView<EarningsSummary>(
      value: summary,
      onRetry: () => ref.invalidate(earningsSummaryProvider),
      onSignIn: () => ref.read(authActionsProvider).logout(),
      builder: (context, data) => RefreshIndicator(
        onRefresh: () async => ref.refresh(earningsSummaryProvider.future),
        child: ListView(
          key: const Key('earnings-list'),
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            CashDebtBanner(position: data.wallet),
            if (data.wallet.hasCashDebt) const SizedBox(height: AppSpacing.md),
            SegmentedButton<EarningsPeriod>(
              key: const Key('earnings-period'),
              segments: [
                ButtonSegment(value: EarningsPeriod.day, label: Text(l10n.ernDay)),
                ButtonSegment(value: EarningsPeriod.week, label: Text(l10n.ernWeek)),
                ButtonSegment(value: EarningsPeriod.month, label: Text(l10n.ernMonth)),
              ],
              selected: {period},
              onSelectionChanged: (s) => ref.read(earningsPeriodProvider.notifier).select(s.first),
            ),
            const SizedBox(height: AppSpacing.md),
            _TotalsCard(summary: data),
            if (data.series.length > 1) ...[
              const SizedBox(height: AppSpacing.md),
              _SeriesChart(summary: data),
            ],
            const SizedBox(height: AppSpacing.md),
            _CategoriesCard(summary: data),
            const SizedBox(height: AppSpacing.md),
            _WalletCard(summary: data),
            const SizedBox(height: AppSpacing.md),
            ListTile(
              key: const Key('earnings-lines'),
              leading: const Icon(Icons.list_alt_outlined),
              title: Text(l10n.ernBreakdownLink),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => context.push(Routes.earningLines),
            ),
            ListTile(
              key: const Key('earnings-ledger'),
              leading: const Icon(Icons.account_balance_wallet_outlined),
              title: Text(l10n.walLedgerTitle),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => context.push(Routes.walletLedger),
            ),
            ListTile(
              key: const Key('earnings-payouts'),
              leading: const Icon(Icons.payments_outlined),
              title: Text(l10n.poTitle),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => context.push(Routes.payouts),
            ),
            if (ref.watch(growthFeatureProvider(GrowthFlags.incentives)))
              ListTile(
                key: const Key('earnings-incentives'),
                leading: const Icon(Icons.emoji_events_outlined),
                title: Text(l10n.incTile),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push(Routes.incentives),
              ),
            if (ref.watch(growthFeatureProvider(GrowthFlags.referrals)))
              ListTile(
                key: const Key('earnings-referral'),
                leading: const Icon(Icons.card_giftcard_outlined),
                title: Text(l10n.refTile),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push(Routes.referral),
              ),
          ],
        ),
      ),
    );
  }
}

class _TotalsCard extends ConsumerWidget {
  const _TotalsCard({required this.summary});

  final EarningsSummary summary;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.ernEarned, style: theme.textTheme.bodyMedium),
            Text(
              f.format(summary.earned),
              key: const Key('earnings-total'),
              textDirection: TextDirection.ltr,
              style: theme.textTheme.displaySmall?.copyWith(fontWeight: FontWeight.w700),
            ),
            Text(l10n.ernJobs(summary.jobs)),
            const Divider(),
            AmountRow(label: l10n.ernGross, value: f.format(summary.gross)),
            AmountRow(label: l10n.ernCommission, value: '-${f.format(summary.commission)}'),
            if (!summary.discountProvider.isZero) AmountRow(label: l10n.ernProviderDiscount, value: '-${f.format(summary.discountProvider)}'),
            if (!summary.tips.isZero) AmountRow(label: l10n.ernTips, value: f.format(summary.tips)),
            AmountRow(label: l10n.ernNet, value: f.format(summary.net), bold: true),
            if (!summary.cashCollected.isZero) AmountRow(label: l10n.ernCash, value: f.format(summary.cashCollected)),
          ],
        ),
      ),
    );
  }
}

class _SeriesChart extends ConsumerWidget {
  const _SeriesChart({required this.summary});

  final EarningsSummary summary;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final peak = summary.series.fold<int>(0, (m, d) => d.earnings.minor > m ? d.earnings.minor : m);
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.ernDaily, style: theme.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.sm),
            SizedBox(
              height: 96,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  for (final d in summary.series)
                    Expanded(
                      child: Tooltip(
                        message: '${d.date}: ${f.format(d.earnings)}',
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 2),
                          child: Align(
                            alignment: Alignment.bottomCenter,
                            child: FractionallySizedBox(
                              heightFactor: peak == 0 ? 0.02 : (d.earnings.minor / peak).clamp(0.02, 1.0),
                              child: DecoratedBox(
                                decoration: BoxDecoration(color: AppColors.brand, borderRadius: BorderRadius.circular(3)),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CategoriesCard extends ConsumerWidget {
  const _CategoriesCard({required this.summary});

  final EarningsSummary summary;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final language = Localizations.localeOf(context).languageCode;
    return Card(
      key: const Key('earnings-categories'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.ernByCategory, style: theme.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.xs),
            if (summary.byCategory.isEmpty)
              Text(l10n.ernNoJobs, key: const Key('earnings-empty'))
            else
              for (final c in summary.byCategory)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(c.name.resolve(language).isEmpty ? (c.code ?? '') : c.name.resolve(language)),
                  subtitle: Text(l10n.ernJobs(c.jobs)),
                  trailing: Text(f.format(c.earnings), textDirection: TextDirection.ltr, style: theme.textTheme.titleMedium),
                ),
          ],
        ),
      ),
    );
  }
}

class _WalletCard extends ConsumerWidget {
  const _WalletCard({required this.summary});

  final EarningsSummary summary;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final w = summary.wallet;
    return Card(
      key: const Key('earnings-wallet'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.walLedgerTitle, style: theme.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.xs),
            AmountRow(label: l10n.ernWalletBalance, value: f.format(w.balance)),
            if (!w.reserved.isZero) AmountRow(label: l10n.ernWalletReserved, value: f.format(w.reserved)),
            AmountRow(label: l10n.ernWalletAvailable, value: f.format(w.available), bold: true),
          ],
        ),
      ),
    );
  }
}
