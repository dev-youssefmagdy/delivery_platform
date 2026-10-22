import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../providers/payments_providers.dart';
import '../widgets/payment_flow_panel.dart';

/// The Wallet tab (also opened by wallet notifications): balance, the debt of
/// unpaid cancellation fees with a way to settle it, the statement (refunds and
/// adjustments included) and the saved payment methods.
class WalletScreen extends ConsumerWidget {
  const WalletScreen({super.key, this.showBack = false});

  final bool showBack;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final wallet = ref.watch(walletProvider);
    final notifier = ref.read(walletProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.walTitle), automaticallyImplyLeading: showBack),
      body: SafeArea(
        child: AsyncStateView<WalletState>(
          value: wallet,
          onRetry: notifier.reload,
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => RefreshIndicator(onRefresh: notifier.refresh, child: _WalletBody(state: data)),
        ),
      ),
    );
  }
}

class _WalletBody extends ConsumerWidget {
  const _WalletBody({required this.state});

  final WalletState state;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final primary = state.primary;
    final lines = state.lines;
    return ListView.builder(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      itemCount: 3 + (lines.isEmpty ? 1 : lines.length) + (state.hasMore ? 1 : 0),
      itemBuilder: (context, i) {
        if (i == 0) {
          return Card(
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(l10n.walBalance, style: theme.textTheme.bodyMedium),
                  const SizedBox(height: AppSpacing.xs),
                  if (primary == null)
                    Text(l10n.walNoWallet, key: const Key('wallet-none'))
                  else
                    Text(
                      formatter.format(primary.balance),
                      key: const Key('wallet-balance'),
                      textDirection: TextDirection.ltr,
                      style: theme.textTheme.headlineMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: primary.inDebt ? AppColors.danger : null,
                      ),
                    ),
                ],
              ),
            ),
          );
        }
        if (i == 1) {
          if (primary != null && primary.inDebt) {
            return Padding(
              padding: const EdgeInsetsDirectional.only(top: AppSpacing.md),
              child: _DebtCard(debt: primary.debt),
            );
          }
          return const SizedBox.shrink();
        }
        if (i == 2) {
          return Padding(
            padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm, bottom: AppSpacing.sm),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ListTile(
                  key: const Key('wallet-methods'),
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.credit_card_outlined),
                  title: Text(l10n.pmTitle),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => context.push(Routes.paymentMethods),
                ),
                const Divider(),
                Text(l10n.walTransactions, style: theme.textTheme.titleMedium),
              ],
            ),
          );
        }
        final index = i - 3;
        if (lines.isEmpty) {
          return Padding(
            padding: const EdgeInsetsDirectional.symmetric(vertical: AppSpacing.lg),
            child: EmptyState(key: const Key('wallet-empty'), icon: Icons.receipt_long_outlined, title: l10n.walEmptyTitle, message: l10n.walEmptyBody),
          );
        }
        if (index >= lines.length) {
          return Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: state.loadingMore
                ? const Center(child: CircularProgressIndicator())
                : TextButton(
                    key: const Key('wallet-more'),
                    onPressed: ref.read(walletProvider.notifier).loadMore,
                    child: Text(l10n.walLoadMore),
                  ),
          );
        }
        return WalletLineTile(line: lines[index], formatter: formatter, balanceLabel: l10n.walBalanceAfter);
      },
    );
  }
}

class _DebtCard extends ConsumerStatefulWidget {
  const _DebtCard({required this.debt});

  final Money debt;

  @override
  ConsumerState<_DebtCard> createState() => _DebtCardState();
}

class _DebtCardState extends ConsumerState<_DebtCard> {
  bool _open = false;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    return Card(
      key: const Key('wallet-debt-card'),
      color: AppColors.danger.withValues(alpha: 0.08),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                const Icon(Icons.warning_amber_rounded, color: AppColors.danger),
                const SizedBox(width: AppSpacing.sm),
                Expanded(child: Text(l10n.walDebtTitle, style: theme.textTheme.titleMedium)),
              ],
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(l10n.walDebtBody(formatter.format(widget.debt))),
            const SizedBox(height: AppSpacing.sm),
            if (!_open)
              AppButton(key: const Key('wallet-settle'), label: l10n.walSettleDebt, onPressed: () => setState(() => _open = true))
            else
              PaymentFlowPanel(
                args: const PayArgs.debt(),
                method: PaymentMethodType.card,
                payLabel: l10n.walSettlePay(formatter.format(widget.debt)),
              ),
          ],
        ),
      ),
    );
  }
}
