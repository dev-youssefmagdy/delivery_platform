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

/// One payout request: the amounts, where it stands, why it was rejected or
/// failed, and cancel while it is still open. Also the target of payout notifications.
class PayoutDetailScreen extends ConsumerStatefulWidget {
  const PayoutDetailScreen({super.key, required this.payoutId});

  final String payoutId;

  @override
  ConsumerState<PayoutDetailScreen> createState() => _PayoutDetailScreenState();
}

class _PayoutDetailScreenState extends ConsumerState<PayoutDetailScreen> {
  bool _busy = false;

  Future<void> _cancel() async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final ok = await showConfirmSheet(
      context,
      title: l10n.poCancelTitle,
      message: l10n.poCancelBody,
      confirmLabel: l10n.poCancelConfirm,
      cancelLabel: l10n.actionCancel,
      destructive: true,
    );
    if (!ok || !mounted) return;
    setState(() => _busy = true);
    try {
      await ref.read(payoutsProvider.notifier).cancel(widget.payoutId);
      messenger.showSnackBar(SnackBar(content: Text(l10n.poCancelled)));
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(moneyErrorText(l10n, e))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final payout = ref.watch(payoutDetailProvider(widget.payoutId));
    return Scaffold(
      appBar: AppBar(title: Text(l10n.poDetailTitle)),
      body: SafeArea(
        child: AsyncStateView<PayoutRequest>(
          value: payout,
          onRetry: () => ref.invalidate(payoutDetailProvider(widget.payoutId)),
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, p) => RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(payoutDetailProvider(widget.payoutId));
              await ref.read(payoutDetailProvider(widget.payoutId).future);
            },
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              children: [
                Center(child: StatusChip(label: payoutStatusText(l10n, p.status, fallback: p.statusLabel), tone: payoutStatusTone(p.status))),
                const SizedBox(height: AppSpacing.sm),
                Text(payoutStatusExplain(l10n, p), key: const Key('payout-explain'), textAlign: TextAlign.center),
                if (p.returned) ...[
                  const SizedBox(height: AppSpacing.xs),
                  Text(l10n.poMoneyBack, textAlign: TextAlign.center, style: theme.textTheme.bodySmall),
                ],
                const SizedBox(height: AppSpacing.md),
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                    child: Column(
                      children: [
                        AmountRow(label: l10n.poAmount, value: f.format(p.amount)),
                        AmountRow(label: l10n.poFee, value: '-${f.format(p.fee)}'),
                        AmountRow(label: l10n.poNet, value: f.format(p.net), bold: true),
                        if (p.methodLabel != null && p.methodLabel!.isNotEmpty) AmountRow(label: l10n.poMethod, value: p.methodLabel!),
                        AmountRow(label: l10n.poRequestedAt, value: formatFinanceTime(context, p.requestedAt)),
                        if (p.paidAt != null) AmountRow(label: l10n.poPaidAt, value: formatFinanceTime(context, p.paidAt)),
                        if (p.transferReference != null) AmountRow(label: l10n.poReference, value: p.transferReference!),
                      ],
                    ),
                  ),
                ),
                if (p.rejectionReason != null && p.rejectionReason!.isNotEmpty) ...[
                  const SizedBox(height: AppSpacing.md),
                  Text('${l10n.poReason}: ${p.rejectionReason}', key: const Key('payout-rejection')),
                ],
                if (p.failureReason != null && p.failureReason!.isNotEmpty) ...[
                  const SizedBox(height: AppSpacing.md),
                  Text('${l10n.poReason}: ${p.failureReason}', key: const Key('payout-failure')),
                ],
                const SizedBox(height: AppSpacing.lg),
                if (p.cancellable)
                  AppButton(key: const Key('payout-cancel'), label: l10n.poCancel, secondary: true, loading: _busy, onPressed: _cancel),
                const SizedBox(height: AppSpacing.sm),
                AppButton(
                  key: const Key('payout-help'),
                  label: l10n.poAskSupport,
                  icon: Icons.support_agent_outlined,
                  secondary: true,
                  onPressed: () => context.push(Routes.payoutSupport(p.id)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
