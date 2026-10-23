import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/earnings_providers.dart';

/// Adds or edits a payout method. Saved details are never sent back by the
/// server, so editing means entering them again; the verification is repeated.
class PayoutMethodFormScreen extends ConsumerStatefulWidget {
  const PayoutMethodFormScreen({super.key, this.editing});

  final PayoutMethod? editing;

  @override
  ConsumerState<PayoutMethodFormScreen> createState() =>
      _PayoutMethodFormScreenState();
}

class _PayoutMethodFormScreenState
    extends ConsumerState<PayoutMethodFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late PayoutMethodKind _kind =
      widget.editing?.kind == PayoutMethodKind.mobileWallet
      ? PayoutMethodKind.mobileWallet
      : PayoutMethodKind.bankAccount;
  late final _label = TextEditingController(text: widget.editing?.label ?? '');
  final _holder = TextEditingController();
  final _bank = TextEditingController();
  final _account = TextEditingController();
  final _wallet = TextEditingController();
  late bool _default = widget.editing?.isDefault ?? false;
  // One intent id per open form: a retry after a failure keeps the same Idempotency-Key.
  final _intentId = newIdempotencyKey();
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    for (final c in [_label, _holder, _bank, _account, _wallet]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context);
    final router = GoRouter.of(context);
    final messenger = ScaffoldMessenger.of(context);
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    final input = NewPayoutMethodInput(
      kind: _kind,
      label: _label.text.trim(),
      holderName: _holder.text.trim(),
      bankName: _kind == PayoutMethodKind.bankAccount
          ? _bank.text.trim()
          : null,
      accountNumber: _kind == PayoutMethodKind.bankAccount
          ? _account.text.trim()
          : null,
      walletNumber: _kind == PayoutMethodKind.mobileWallet
          ? _wallet.text.trim()
          : null,
      isDefault: _default,
    );
    try {
      await ref
          .read(payoutMethodsProvider.notifier)
          .save(input, intentId: _intentId, editingId: widget.editing?.id);
      messenger.showSnackBar(SnackBar(content: Text(l10n.pomSaved)));
      if (mounted) router.pop();
    } on Object catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = moneyErrorText(l10n, e);
      });
    }
  }

  String? _required(String? v, AppLocalizations l10n) =>
      (v == null || v.trim().isEmpty) ? l10n.fieldRequired : null;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final bank = _kind == PayoutMethodKind.bankAccount;
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.editing == null ? l10n.pomAdd : l10n.pomEdit),
      ),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            children: [
              if (widget.editing != null) ...[
                Text(l10n.pomEditNote, style: theme.textTheme.bodyMedium),
                const SizedBox(height: AppSpacing.md),
              ],
              SegmentedButton<PayoutMethodKind>(
                key: const Key('pm-kind'),
                segments: [
                  ButtonSegment(
                    value: PayoutMethodKind.bankAccount,
                    label: Text(l10n.pomBank),
                  ),
                  ButtonSegment(
                    value: PayoutMethodKind.mobileWallet,
                    label: Text(l10n.pomWallet),
                  ),
                ],
                selected: {_kind},
                onSelectionChanged: widget.editing != null
                    ? null
                    : (s) => setState(() => _kind = s.first),
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                key: const Key('pm-label'),
                controller: _label,
                decoration: InputDecoration(labelText: l10n.pomLabel),
                validator: (v) => _required(v, l10n),
              ),
              const SizedBox(height: AppSpacing.sm),
              TextFormField(
                key: const Key('pm-holder'),
                controller: _holder,
                decoration: InputDecoration(labelText: l10n.pomHolder),
                validator: (v) => _required(v, l10n),
              ),
              const SizedBox(height: AppSpacing.sm),
              if (bank) ...[
                TextFormField(
                  key: const Key('pm-bank'),
                  controller: _bank,
                  decoration: InputDecoration(labelText: l10n.pomBankName),
                  validator: (v) => _required(v, l10n),
                ),
                const SizedBox(height: AppSpacing.sm),
                TextFormField(
                  key: const Key('pm-account'),
                  controller: _account,
                  textDirection: TextDirection.ltr,
                  decoration: InputDecoration(labelText: l10n.pomAccount),
                  validator: (v) =>
                      RegExp(r'^[A-Za-z0-9 \-]{4,40}$')
                          .hasMatch((v ?? '').trim())
                      ? null
                      : l10n.pomAccountInvalid,
                ),
              ] else
                TextFormField(
                  key: const Key('pm-wallet-number'),
                  controller: _wallet,
                  keyboardType: TextInputType.phone,
                  textDirection: TextDirection.ltr,
                  decoration: InputDecoration(labelText: l10n.pomWalletNumber),
                  validator: (v) =>
                      RegExp(r'^\+?[0-9 \-]{8,24}$').hasMatch((v ?? '').trim())
                      ? null
                      : l10n.pomWalletInvalid,
                ),
              SwitchListTile(
                key: const Key('pm-default-switch'),
                contentPadding: EdgeInsets.zero,
                title: Text(l10n.pomMakeDefault),
                value: _default,
                onChanged: (v) => setState(() => _default = v),
              ),
              Text(l10n.pomVerifyNote, style: theme.textTheme.bodySmall),
              const SizedBox(height: AppSpacing.md),
              if (_error != null)
                Padding(
                  padding: const EdgeInsetsDirectional.only(
                    bottom: AppSpacing.sm,
                  ),
                  child: Text(
                    _error!,
                    key: const Key('pm-error'),
                    style: TextStyle(color: theme.colorScheme.error),
                  ),
                ),
              AppButton(
                key: const Key('pm-save'),
                label: l10n.pomSave,
                loading: _busy,
                onPressed: _save,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
