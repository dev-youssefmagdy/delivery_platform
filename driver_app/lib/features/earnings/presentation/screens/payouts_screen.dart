import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../providers/earnings_providers.dart';
import '../widgets/payout_texts.dart';

/// Payouts: what can be withdrawn, a button to request one, the payout methods,
/// and the history with each request's status (reviewed, paid, rejected ...).
class PayoutsScreen extends ConsumerWidget {
  const PayoutsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final payouts = ref.watch(payoutsProvider);
    final notifier = ref.read(payoutsProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.poTitle)),
      body: SafeArea(
        child: AsyncStateView<PayoutsState>(
          value: payouts,
          onRetry: notifier.reload,
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, data) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: _Body(data: data),
          ),
        ),
      ),
    );
  }
}

class _Body extends ConsumerWidget {
  const _Body({required this.data});

  final PayoutsState data;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final balance = data.balance;
    return ListView.builder(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      itemCount: 2 + (data.items.isEmpty ? 1 : data.items.length) + (data.hasMore ? 1 : 0),
      itemBuilder: (context, i) {
        if (i == 0) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Card(
                margin: EdgeInsets.zero,
                child: Padding(
                  padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(l10n.poAvailable, style: theme.textTheme.bodyMedium),
                      IsolatedText(
                        balance == null ? '-' : f.format(balance.available),
                        key: const Key('payout-available'),
                        style: theme.textTheme.displaySmall?.copyWith(fontWeight: FontWeight.w700)),
                      if (balance != null && !balance.reserved.isZero) Text(l10n.poReservedNote(f.format(balance.reserved)), style: theme.textTheme.bodySmall),
                      if (balance != null) Text(l10n.poLimits(f.format(balance.min), f.format(balance.max)), style: theme.textTheme.bodySmall),
                      const SizedBox(height: AppSpacing.md),
                      AppButton(
                        key: const Key('payout-request'),
                        label: l10n.poRequest,
                        icon: Icons.south_west,
                        onPressed: balance == null || balance.available.minor <= 0 ? null : () => context.push(Routes.payoutRequest),
                      ),
                    ],
                  ),
                ),
              ),
              ListTile(
                key: const Key('payout-methods-link'),
                leading: const Icon(Icons.account_balance_outlined),
                title: Text(l10n.pomTitle),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push(Routes.payoutMethods),
              ),
              const Divider(),
            ],
          );
        }
        if (i == 1) {
          return Padding(
            padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
            child: Text(l10n.poHistory, style: theme.textTheme.titleMedium),
          );
        }
        if (data.items.isEmpty) {
          return EmptyState(key: const Key('payouts-empty'), icon: Icons.payments_outlined, title: l10n.poEmptyTitle, message: l10n.poEmptyBody);
        }
        final index = i - 2;
        if (index >= data.items.length) {
          return Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: data.loadingMore
                ? const Center(child: CircularProgressIndicator())
                : TextButton(key: const Key('payouts-more'), onPressed: ref.read(payoutsProvider.notifier).loadMore, child: Text(l10n.walLoadMore)),
          );
        }
        final p = data.items[index];
        return Card(
          margin: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
          child: ListTile(
            key: Key('payout-${p.id}'),
            title: IsolatedText(f.format(p.amount), style: theme.textTheme.titleMedium),
            subtitle: Text(formatFinanceTime(context, p.requestedAt)),
            trailing: StatusChip(label: payoutStatusText(l10n, p.status, fallback: p.statusLabel), tone: payoutStatusTone(p.status)),
            onTap: () => context.push(Routes.payout(p.id)),
          ),
        );
      },
    );
  }
}
