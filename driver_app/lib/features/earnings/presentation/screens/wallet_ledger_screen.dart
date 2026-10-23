import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../providers/earnings_providers.dart';
import '../widgets/cash_debt_banner.dart';

/// The wallet ledger: every credit and debit with the balance after it
/// (earnings, commission owed on cash jobs, payouts, adjustments).
class WalletLedgerScreen extends ConsumerWidget {
  const WalletLedgerScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final lines = ref.watch(walletLinesProvider);
    final notifier = ref.read(walletLinesProvider.notifier);
    final position = ref.watch(walletPositionProvider).value;
    final f = ref.watch(moneyFormatterProvider);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.walLedgerTitle)),
      body: SafeArea(
        child: AsyncStateView<PagedState<WalletLine>>(
          value: lines,
          onRetry: notifier.reload,
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, data) => RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(walletPositionProvider);
              await notifier.refresh();
            },
            child: ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              itemCount:
                  1 +
                  (data.items.isEmpty ? 1 : data.items.length) +
                  (data.hasMore ? 1 : 0),
              itemBuilder: (context, i) {
                if (i == 0) {
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      if (position != null) ...[
                        CashDebtBanner(position: position),
                        if (position.hasCashDebt)
                          const SizedBox(height: AppSpacing.md),
                        Card(
                          margin: EdgeInsets.zero,
                          child: Padding(
                            padding: const EdgeInsetsDirectional.all(
                              AppSpacing.md,
                            ),
                            child: Column(
                              children: [
                                AmountRow(
                                  label: l10n.ernWalletBalance,
                                  value: f.format(position.balance),
                                ),
                                AmountRow(
                                  label: l10n.ernWalletAvailable,
                                  value: f.format(position.available),
                                  bold: true,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                      const SizedBox(height: AppSpacing.sm),
                    ],
                  );
                }
                if (data.items.isEmpty) {
                  return EmptyState(
                    key: const Key('ledger-empty'),
                    icon: Icons.receipt_long_outlined,
                    title: l10n.walLedgerEmpty,
                  );
                }
                final index = i - 1;
                if (index >= data.items.length) {
                  return Padding(
                    padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                    child: data.loadingMore
                        ? const Center(child: CircularProgressIndicator())
                        : TextButton(
                            key: const Key('ledger-more'),
                            onPressed: notifier.loadMore,
                            child: Text(l10n.walLoadMore),
                          ),
                  );
                }
                return WalletLineTile(
                  line: data.items[index],
                  formatter: f,
                  balanceLabel: l10n.walBalanceAfter,
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}
