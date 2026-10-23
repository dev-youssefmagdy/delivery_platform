import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../providers/earnings_providers.dart';

/// Payout methods (bank account or mobile wallet): list with verification
/// state, add, edit (re-enter the details), make default and delete. The
/// details are never shown again after saving; finance verifies a method before it can be used.
class PayoutMethodsScreen extends ConsumerStatefulWidget {
  const PayoutMethodsScreen({super.key});

  @override
  ConsumerState<PayoutMethodsScreen> createState() =>
      _PayoutMethodsScreenState();
}

class _PayoutMethodsScreenState extends ConsumerState<PayoutMethodsScreen> {
  String? _busyId;

  Future<void> _run(
    String id,
    Future<void> Function() action, {
    String? done,
  }) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busyId = id);
    try {
      await action();
      if (done != null) messenger.showSnackBar(SnackBar(content: Text(done)));
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(moneyErrorText(l10n, e))));
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  Future<void> _delete(PayoutMethod m) async {
    final l10n = AppLocalizations.of(context);
    final ok = await showConfirmSheet(
      context,
      title: l10n.pomDeleteTitle,
      message: l10n.pomDeleteBody,
      confirmLabel: l10n.pomDelete,
      cancelLabel: l10n.actionCancel,
      destructive: true,
    );
    if (!ok || !mounted) return;
    await _run(
      m.id,
      () => ref.read(payoutMethodsProvider.notifier).delete(m.id),
      done: l10n.pomDeleted,
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final methods = ref.watch(payoutMethodsProvider);
    final notifier = ref.read(payoutMethodsProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.pomTitle)),
      floatingActionButton: FloatingActionButton.extended(
        key: const Key('payout-method-add'),
        onPressed: () => context.push(Routes.payoutMethodNew),
        icon: const Icon(Icons.add),
        label: Text(l10n.pomAdd),
      ),
      body: SafeArea(
        child: AsyncStateView<List<PayoutMethod>>(
          value: methods,
          onRetry: notifier.reload,
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, list) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: list.isEmpty
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      Padding(
                        padding: const EdgeInsetsDirectional.only(
                          top: AppSpacing.xl,
                        ),
                        child: EmptyState(
                          key: const Key('methods-empty'),
                          icon: Icons.account_balance_outlined,
                          title: l10n.pomEmptyTitle,
                          message: l10n.pomEmptyBody,
                        ),
                      ),
                    ],
                  )
                : ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsetsDirectional.fromSTEB(
                      AppSpacing.md,
                      AppSpacing.md,
                      AppSpacing.md,
                      96,
                    ),
                    children: [
                      for (final m in list)
                        Card(
                          margin: const EdgeInsetsDirectional.only(
                            bottom: AppSpacing.sm,
                          ),
                          child: Padding(
                            padding: const EdgeInsetsDirectional.all(
                              AppSpacing.md,
                            ),
                            child: Column(
                              key: Key('pm-${m.id}'),
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(
                                      m.kind == PayoutMethodKind.mobileWallet
                                          ? Icons.phone_android
                                          : Icons.account_balance,
                                    ),
                                    const SizedBox(width: AppSpacing.sm),
                                    Expanded(
                                      child: Text(
                                        m.label,
                                        style: theme.textTheme.titleMedium,
                                      ),
                                    ),
                                    StatusChip(
                                      label: m.verified
                                          ? l10n.pomVerified
                                          : l10n.pomUnverified,
                                      tone: m.verified
                                          ? ChipTone.success
                                          : ChipTone.warning,
                                    ),
                                  ],
                                ),
                                Text(
                                  '${m.kindLabel}${m.last4 == null ? '' : ' ${ltrIsolate('•••• ${m.last4}')}'}',
                                ),
                                if (m.isDefault)
                                  Text(
                                    l10n.pomDefault,
                                    style: theme.textTheme.bodySmall,
                                  ),
                                if (!m.verified)
                                  Text(
                                    l10n.pomUnverifiedNote,
                                    style: theme.textTheme.bodySmall,
                                  ),
                                const SizedBox(height: AppSpacing.xs),
                                if (_busyId == m.id)
                                  const LinearProgressIndicator()
                                else
                                  Wrap(
                                    spacing: AppSpacing.xs,
                                    children: [
                                      if (!m.isDefault)
                                        TextButton(
                                          key: Key('pm-default-${m.id}'),
                                          onPressed: () => _run(
                                            m.id,
                                            () => notifier.makeDefault(m.id),
                                            done: l10n.pomDefaultSet,
                                          ),
                                          child: Text(l10n.pomMakeDefault),
                                        ),
                                      TextButton(
                                        key: Key('pm-edit-${m.id}'),
                                        onPressed: () => context.push(
                                          Routes.payoutMethodNew,
                                          extra: m,
                                        ),
                                        child: Text(l10n.pomEdit),
                                      ),
                                      TextButton(
                                        key: Key('pm-delete-${m.id}'),
                                        onPressed: () => _delete(m),
                                        child: Text(l10n.pomDelete),
                                      ),
                                    ],
                                  ),
                              ],
                            ),
                          ),
                        ),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}
