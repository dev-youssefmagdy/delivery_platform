import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../jobs/domain/amount_input.dart';
import '../providers/earnings_providers.dart';

/// Requests a payout: amount and a verified method. The amount is reserved in
/// the wallet at once; the fee and approval are the server's. The same request
/// is retried with the same Idempotency-Key after a network drop.
class PayoutRequestScreen extends ConsumerStatefulWidget {
  const PayoutRequestScreen({super.key});

  @override
  ConsumerState<PayoutRequestScreen> createState() => _PayoutRequestScreenState();
}

class _PayoutRequestScreenState extends ConsumerState<PayoutRequestScreen> {
  final _amount = TextEditingController();
  String? _methodId;
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _amount.dispose();
    super.dispose();
  }

  int get _exponent => ref.read(appConfigProvider).value?.currencyExponent ?? 2;

  Future<void> _submit(PayoutBalance balance, List<PayoutMethod> methods) async {
    final l10n = AppLocalizations.of(context);
    final router = GoRouter.of(context);
    final f = ref.read(moneyFormatterProvider);
    final minor = parseAmountMinor(_amount.text, exponent: _exponent);
    final method = methods.where((m) => m.id == _methodId).firstOrNull;
    String? problem;
    if (minor == null || minor <= 0) {
      problem = l10n.poErrAmountInvalid;
    } else if (minor < balance.min.minor) {
      problem = l10n.poErrBelowMinAmount(f.format(balance.min));
    } else if (minor > balance.available.minor) {
      problem = l10n.poErrInsufficient;
    } else if (balance.max.minor > 0 && minor > balance.max.minor) {
      problem = l10n.poErrAboveMaxAmount(f.format(balance.max));
    } else if (method == null) {
      problem = l10n.poErrChooseMethod;
    }
    if (problem != null) {
      setState(() => _error = problem);
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final payout = await ref.read(payoutsProvider.notifier).request(amountMinor: minor!, methodId: method!.id);
      if (!mounted) return;
      router.pushReplacement(Routes.payout(payout.id));
    } on Object catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = moneyErrorText(l10n, e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final payouts = ref.watch(payoutsProvider);
    final methods = ref.watch(payoutMethodsProvider);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.poRequest)),
      body: SafeArea(
        child: AsyncStateView<PayoutsState>(
          value: payouts,
          onRetry: ref.read(payoutsProvider.notifier).reload,
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, data) {
            final balance = data.balance;
            if (balance == null) return EmptyState(title: l10n.poNoBalance);
            return AsyncStateView<List<PayoutMethod>>(
              value: methods,
              onRetry: ref.read(payoutMethodsProvider.notifier).reload,
              onSignIn: () => ref.read(authActionsProvider).logout(),
              builder: (context, list) {
                final usable = [for (final m in list) if (m.verified) m];
                if (_methodId == null && usable.isNotEmpty) {
                  _methodId = (usable.where((m) => m.isDefault).firstOrNull ?? usable.first).id;
                }
                return ListView(
                  padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                  children: [
                    Text(l10n.poAvailable, style: theme.textTheme.bodyMedium),
                    IsolatedText(f.format(balance.available), style: theme.textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w700)),
                    Text(l10n.poLimits(f.format(balance.min), f.format(balance.max)), style: theme.textTheme.bodySmall),
                    const SizedBox(height: AppSpacing.md),
                    TextField(
                      key: const Key('payout-amount'),
                      controller: _amount,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
                      textDirection: TextDirection.ltr,
                      decoration: InputDecoration(
                        labelText: l10n.poAmountLabel(balance.available.currency),
                        suffix: TextButton(
                          key: const Key('payout-all'),
                          onPressed: () => _amount.text = amountInputText(balance.available.minor, exponent: _exponent),
                          child: Text(l10n.poAll),
                        ),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    Text(l10n.poMethod, style: theme.textTheme.titleMedium),
                    if (usable.isEmpty)
                      Padding(
                        padding: const EdgeInsetsDirectional.symmetric(vertical: AppSpacing.sm),
                        child: Text(list.isEmpty ? l10n.poNoMethods : l10n.poNoVerifiedMethods, key: const Key('payout-no-method')),
                      )
                    else
                      RadioGroup<String>(
                        groupValue: _methodId,
                        onChanged: (v) => setState(() => _methodId = v),
                        child: Column(
                          children: [
                            for (final m in usable)
                              RadioListTile<String>(
                                key: Key('payout-method-${m.id}'),
                                contentPadding: EdgeInsets.zero,
                                value: m.id,
                                title: Text(m.label),
                                subtitle: Text('${m.kindLabel}${m.last4 == null ? '' : ' •••• ${m.last4}'}'),
                              ),
                          ],
                        ),
                      ),
                    TextButton(onPressed: () => context.push(Routes.payoutMethods), child: Text(l10n.pomManage)),
                    const SizedBox(height: AppSpacing.sm),
                    Text(l10n.poFeeNote, style: theme.textTheme.bodySmall),
                    const SizedBox(height: AppSpacing.md),
                    if (_error != null)
                      Padding(
                        padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
                        child: Text(_error!, key: const Key('payout-error'), style: TextStyle(color: theme.colorScheme.error)),
                      ),
                    AppButton(
                      key: const Key('payout-submit'),
                      label: l10n.poSubmit,
                      loading: _busy,
                      onPressed: usable.isEmpty ? null : () => _submit(balance, list),
                    ),
                  ],
                );
              },
            );
          },
        ),
      ),
    );
  }
}
