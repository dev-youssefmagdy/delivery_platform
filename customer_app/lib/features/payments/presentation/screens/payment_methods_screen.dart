import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../providers/payments_providers.dart';

/// Saved payment methods. The card is entered only on the gateway's own page;
/// a new method shows as pending until the gateway confirms it to the server.
class PaymentMethodsScreen extends ConsumerStatefulWidget {
  const PaymentMethodsScreen({super.key});

  @override
  ConsumerState<PaymentMethodsScreen> createState() =>
      _PaymentMethodsScreenState();
}

class _PaymentMethodsScreenState extends ConsumerState<PaymentMethodsScreen>
    with WidgetsBindingObserver {
  bool _adding = false;
  String? _deleting;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// Back from the gateway page: the method may have become active meanwhile.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.read(paymentMethodsProvider.notifier).refresh();
    }
  }

  Future<void> _add(PaymentMethodType type) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _adding = true);
    try {
      final setup = await ref.read(paymentMethodsProvider.notifier).setup(type);
      final url = setup.action?.url;
      if (url != null) {
        final ok = await ref.read(gatewayLauncherProvider)(url);
        if (!ok) {
          messenger.showSnackBar(SnackBar(content: Text(l10n.payLaunchFailed)));
        }
      } else {
        messenger.showSnackBar(SnackBar(content: Text(l10n.pmSetupPending)));
      }
    } on Object catch (e) {
      messenger.showSnackBar(
        SnackBar(content: Text(paymentErrorText(l10n, e))),
      );
    } finally {
      if (mounted) setState(() => _adding = false);
    }
  }

  Future<void> _delete(SavedPaymentMethod m) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final ok = await showConfirmSheet(
      context,
      title: l10n.pmDeleteTitle,
      message: l10n.pmDeleteBody,
      confirmLabel: l10n.pmDelete,
      cancelLabel: l10n.actionCancel,
      destructive: true,
    );
    if (!ok || !mounted) return;
    setState(() => _deleting = m.id);
    try {
      await ref.read(paymentMethodsProvider.notifier).delete(m.id);
      messenger.showSnackBar(SnackBar(content: Text(l10n.pmDeleted)));
    } on Object catch (e) {
      messenger.showSnackBar(
        SnackBar(content: Text(paymentErrorText(l10n, e))),
      );
    } finally {
      if (mounted) setState(() => _deleting = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final methods = ref.watch(paymentMethodsProvider);
    final notifier = ref.read(paymentMethodsProvider.notifier);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.pmTitle)),
      body: SafeArea(
        child: AsyncStateView<List<SavedPaymentMethod>>(
          value: methods,
          onRetry: notifier.reload,
          onSignIn: () => context.go(Routes.auth),
          builder: (context, list) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              children: [
                Text(
                  l10n.pmIntro,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: AppSpacing.md),
                if (list.isEmpty)
                  EmptyState(
                    key: const Key('methods-empty'),
                    icon: Icons.credit_card_off_outlined,
                    title: l10n.pmEmptyTitle,
                    message: l10n.pmEmptyBody,
                  ),
                for (final m in list)
                  Card(
                    margin: const EdgeInsetsDirectional.only(
                      bottom: AppSpacing.sm,
                    ),
                    child: ListTile(
                      key: Key('method-${m.id}'),
                      leading: Icon(
                        m.type == PaymentMethodType.mobileWallet
                            ? Icons.phone_android
                            : Icons.credit_card,
                      ),
                      title: Text(
                        m.type == PaymentMethodType.mobileWallet
                            ? l10n.pmWallet
                            : '${m.brand ?? l10n.pmCard} ${ltrIsolate('•••• ${m.last4 ?? ''}')}',
                      ),
                      subtitle: m.isPending
                          ? Text(l10n.pmPending)
                          : (m.isDefault ? Text(l10n.pmDefault) : null),
                      trailing: _deleting == m.id
                          ? const SizedBox.square(
                              dimension: 24,
                              child: CircularProgressIndicator(
                                strokeWidth: 2.5,
                              ),
                            )
                          : IconButton(
                              key: Key('method-delete-${m.id}'),
                              tooltip: l10n.pmDelete,
                              icon: const Icon(Icons.delete_outline),
                              onPressed: () => _delete(m),
                            ),
                    ),
                  ),
                const SizedBox(height: AppSpacing.md),
                AppButton(
                  key: const Key('method-add-card'),
                  label: l10n.pmAddCard,
                  icon: Icons.add_card,
                  loading: _adding,
                  onPressed: () => _add(PaymentMethodType.card),
                ),
                const SizedBox(height: AppSpacing.sm),
                AppButton(
                  key: const Key('method-add-wallet'),
                  label: l10n.pmAddWallet,
                  icon: Icons.phone_android,
                  secondary: true,
                  onPressed: _adding
                      ? null
                      : () => _add(PaymentMethodType.mobileWallet),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
