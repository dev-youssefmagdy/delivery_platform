import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../providers/payments_providers.dart';

/// Paying one thing (a trip, a tip, the wallet debt): choose how, open the
/// gateway page, then wait for the server to confirm. Every state is handled:
/// idle, starting, awaiting confirmation, paid, failed (recoverable), under
/// review and request errors. This widget never decides that a payment is paid.
class PaymentFlowPanel extends ConsumerStatefulWidget {
  const PaymentFlowPanel({
    super.key,
    required this.args,
    required this.method,
    required this.payLabel,
    this.bookingId,
    this.bookingCode,
    this.onPaid,
  });

  final PayArgs args;

  /// The online method of the booking (card or mobile wallet); a debt is paid by card.
  final PaymentMethodType method;
  final String payLabel;

  /// For "contact support" about a payment under review.
  final String? bookingId;
  final String? bookingCode;
  final VoidCallback? onPaid;

  @override
  ConsumerState<PaymentFlowPanel> createState() => _PaymentFlowPanelState();
}

class _PaymentFlowPanelState extends ConsumerState<PaymentFlowPanel> {
  String? _savedId;
  final _phone = TextEditingController();
  bool _launchFailed = false;

  @override
  void dispose() {
    _phone.dispose();
    super.dispose();
  }

  bool get _isWallet => widget.method == PaymentMethodType.mobileWallet;

  PayWith _pay() => PayWith(
    paymentMethodId: _savedId,
    method: _savedId == null ? widget.method : null,
    walletPhone: _isWallet && _savedId == null && _phone.text.trim().isNotEmpty ? _phone.text.trim() : null,
  );

  Future<void> _open(String url) async {
    final ok = await ref.read(gatewayLauncherProvider)(url);
    if (mounted) setState(() => _launchFailed = !ok);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final flow = ref.watch(paymentFlowProvider(widget.args));
    final notifier = ref.read(paymentFlowProvider(widget.args).notifier);

    ref.listen(paymentFlowProvider(widget.args), (previous, next) {
      final url = next.openUrl;
      if (url != null) {
        notifier.consumeUrl();
        _open(url);
      }
      if (previous?.phase != PayPhase.paid && next.phase == PayPhase.paid) widget.onPaid?.call();
    });

    switch (flow.phase) {
      case PayPhase.paid:
        return _Banner(
          key: const Key('pay-paid'),
          icon: Icons.check_circle,
          color: AppColors.success,
          title: l10n.payPaidTitle,
          body: l10n.payPaidBody,
        );
      case PayPhase.awaiting:
        final url = flow.payment?.action?.url;
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _Banner(
              key: const Key('pay-awaiting'),
              icon: Icons.hourglass_top,
              color: AppColors.info,
              title: l10n.payAwaitingTitle,
              body: l10n.payAwaitingBody,
              busy: true,
            ),
            if (_launchFailed) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(l10n.payLaunchFailed, key: const Key('pay-launch-failed'), style: TextStyle(color: theme.colorScheme.error)),
            ],
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              key: const Key('pay-check'),
              label: l10n.payCheckStatus,
              loading: flow.checking,
              onPressed: notifier.check,
            ),
            if (url != null) ...[
              const SizedBox(height: AppSpacing.sm),
              AppButton(key: const Key('pay-reopen'), label: l10n.payOpenAgain, secondary: true, onPressed: () => _open(url)),
            ],
          ],
        );
      case PayPhase.review:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _Banner(
              key: const Key('pay-review'),
              icon: Icons.manage_search,
              color: AppColors.warning,
              title: l10n.payReviewTitle,
              body: l10n.payReviewBody,
            ),
            if (widget.bookingId != null) ...[
              const SizedBox(height: AppSpacing.sm),
              AppButton(
                key: const Key('pay-review-help'),
                label: l10n.payContactSupport,
                secondary: true,
                onPressed: () => context.push(
                  Routes.paymentSupport(widget.bookingId!, widget.bookingCode ?? '', flow.payment?.id),
                ),
              ),
            ],
          ],
        );
      case PayPhase.failed:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _Banner(
              key: const Key('pay-failed'),
              icon: Icons.error_outline,
              color: AppColors.danger,
              title: l10n.payFailedTitle,
              body: paymentFailureText(l10n, flow.payment?.failureCode),
            ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(key: const Key('pay-try-again'), label: l10n.payTryAgain, onPressed: notifier.reset),
          ],
        );
      case PayPhase.idle:
      case PayPhase.starting:
      case PayPhase.error:
        return _chooser(context, l10n, theme, flow, notifier);
    }
  }

  Widget _chooser(BuildContext context, AppLocalizations l10n, ThemeData theme, PayFlowState flow, PaymentFlowNotifier notifier) {
    final methods = ref.watch(paymentMethodsProvider).value ?? const <SavedPaymentMethod>[];
    final saved = [for (final m in methods) if (m.isActive && m.type == widget.method) m];
    final starting = flow.phase == PayPhase.starting;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (saved.isNotEmpty)
          RadioGroup<String?>(
            groupValue: _savedId,
            onChanged: (v) => setState(() => _savedId = v),
            child: Column(
              children: [
                for (final m in saved)
                  RadioListTile<String?>(
                    key: Key('pay-saved-${m.id}'),
                    contentPadding: EdgeInsets.zero,
                    value: m.id,
                    title: Text('${m.brand ?? l10n.pmCard} •••• ${m.last4 ?? ''}', textDirection: TextDirection.ltr),
                  ),
                RadioListTile<String?>(
                  key: const Key('pay-new-method'),
                  contentPadding: EdgeInsets.zero,
                  value: null,
                  title: Text(_isWallet ? l10n.payNewWallet : l10n.payNewCard),
                ),
              ],
            ),
          ),
        if (_isWallet && _savedId == null) ...[
          TextField(
            key: const Key('pay-wallet-phone'),
            controller: _phone,
            keyboardType: TextInputType.phone,
            textDirection: TextDirection.ltr,
            decoration: InputDecoration(labelText: l10n.payWalletPhone, helperText: l10n.payWalletPhoneHelp),
          ),
          const SizedBox(height: AppSpacing.sm),
        ],
        if (flow.error != null)
          Padding(
            padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
            child: Text(paymentErrorText(l10n, flow.error!), key: const Key('pay-error'), style: TextStyle(color: theme.colorScheme.error)),
          ),
        AppButton(
          key: const Key('pay-start'),
          label: widget.payLabel,
          icon: Icons.lock_outline,
          loading: starting,
          onPressed: () => notifier.start(_pay()),
        ),
        const SizedBox(height: AppSpacing.xs),
        Text(l10n.paySecureNote, style: theme.textTheme.bodySmall, textAlign: TextAlign.center),
      ],
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({super.key, required this.icon, required this.color, required this.title, required this.body, this.busy = false});

  final IconData icon;
  final Color color;
  final String title;
  final String body;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Semantics(
      liveRegion: true,
      child: DecoratedBox(
        decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(AppRadius.md)),
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              busy ? const SizedBox.square(dimension: 24, child: CircularProgressIndicator(strokeWidth: 2.5)) : Icon(icon, color: color),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: theme.textTheme.titleMedium),
                    const SizedBox(height: AppSpacing.xs),
                    Text(body),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
