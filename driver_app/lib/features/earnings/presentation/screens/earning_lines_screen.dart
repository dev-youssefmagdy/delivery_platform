import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../providers/earnings_providers.dart';

/// Booking-level earnings: for every job the fare, commission, discounts, tax,
/// tip and what the driver earned, so a payout can be explained line by line.
class EarningLinesScreen extends ConsumerWidget {
  const EarningLinesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final lines = ref.watch(earningLinesProvider);
    final notifier = ref.read(earningLinesProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.ernBreakdownLink)),
      body: SafeArea(
        child: AsyncStateView<PagedState<EarningLine>>(
          value: lines,
          onRetry: notifier.reload,
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, data) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: data.items.isEmpty
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      Padding(
                        padding: const EdgeInsetsDirectional.only(
                          top: AppSpacing.xl,
                        ),
                        child: EmptyState(
                          key: const Key('lines-empty'),
                          icon: Icons.list_alt_outlined,
                          title: l10n.ernNoJobs,
                        ),
                      ),
                    ],
                  )
                : NotificationListener<ScrollNotification>(
                    onNotification: (n) {
                      if (n.metrics.pixels >= n.metrics.maxScrollExtent - 200) {
                        notifier.loadMore();
                      }
                      return false;
                    },
                    child: ListView.builder(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                      itemCount: data.items.length + (data.loadingMore ? 1 : 0),
                      itemBuilder: (_, i) => i >= data.items.length
                          ? const Center(child: CircularProgressIndicator())
                          : _EarningLineTile(line: data.items[i]),
                    ),
                  ),
          ),
        ),
      ),
    );
  }
}

class _EarningLineTile extends ConsumerWidget {
  const _EarningLineTile({required this.line});

  final EarningLine line;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final language = Localizations.localeOf(context).languageCode;
    return Card(
      margin: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
      child: ExpansionTile(
        key: Key('line-${line.bookingId}'),
        title: Text('${line.bookingCode} · ${line.service.resolve(language)}'),
        subtitle: Text(formatFinanceTime(context, line.completedAt)),
        trailing: IsolatedText(
          f.format(line.providerEarning),
          style: theme.textTheme.titleMedium,
        ),
        childrenPadding: const EdgeInsetsDirectional.fromSTEB(
          AppSpacing.md,
          0,
          AppSpacing.md,
          AppSpacing.md,
        ),
        children: [
          AmountRow(label: l10n.ernGross, value: f.format(line.grossFare)),
          AmountRow(
            label: l10n.ernCommission,
            value: '-${f.format(line.commission)}',
          ),
          if (!line.discountProviderFunded.isZero)
            AmountRow(
              label: l10n.ernProviderDiscount,
              value: '-${f.format(line.discountProviderFunded)}',
            ),
          if (!line.discountPlatformFunded.isZero)
            AmountRow(
              label: l10n.ernPlatformDiscount,
              value: f.format(line.discountPlatformFunded),
            ),
          if (!line.tax.isZero)
            AmountRow(label: l10n.rcpTax, value: f.format(line.tax)),
          if (!line.tip.isZero)
            AmountRow(label: l10n.ernTips, value: f.format(line.tip)),
          AmountRow(label: l10n.ernNet, value: f.format(line.providerNet)),
          AmountRow(
            label: l10n.ernEarned,
            value: f.format(line.providerEarning),
            bold: true,
          ),
          if (line.cashCollected != null)
            AmountRow(
              label: l10n.ernCash,
              value: f.format(line.cashCollected!),
            ),
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text(
              line.paymentStatusLabel ?? line.paymentStatus.value,
              style: theme.textTheme.bodySmall,
            ),
          ),
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: TextButton(
              onPressed: () => context.push('/job/${line.bookingId}'),
              child: Text(l10n.ernOpenJob),
            ),
          ),
        ],
      ),
    );
  }
}
