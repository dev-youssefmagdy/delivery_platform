import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../providers/payments_providers.dart';

/// The immutable receipt of a trip with every line of the breakdown: agreed
/// fare, approved adjustments, coupon discount, service fee, tax, tip and the
/// total the customer pays. The printable version opens in the browser.
class ReceiptScreen extends ConsumerWidget {
  const ReceiptScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final receipt = ref.watch(receiptProvider(bookingId));
    return Scaffold(
      appBar: AppBar(title: Text(l10n.rcpTitle)),
      body: SafeArea(
        child: AsyncStateView<TripReceipt>(
          value: receipt,
          onRetry: () => ref.invalidate(receiptProvider(bookingId)),
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => _ReceiptBody(receipt: data),
        ),
      ),
    );
  }
}

class _ReceiptBody extends ConsumerWidget {
  const _ReceiptBody({required this.receipt});

  final TripReceipt receipt;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final r = receipt;
    String money(Money m) => f.format(m);
    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(l10n.rcpNumber(r.number), style: theme.textTheme.titleMedium, key: const Key('receipt-number')),
                if (r.issuedAt != null) Text(formatFinanceTime(context, r.issuedAt), style: theme.textTheme.bodySmall),
                const SizedBox(height: AppSpacing.sm),
                AmountRow(label: l10n.bkReceiptCode, value: r.bookingCode),
                if (r.counterpartName != null) AmountRow(label: l10n.rcpDriver, value: r.counterpartName!),
                if (r.pickup != null && r.pickup!.isNotEmpty) AmountRow(label: l10n.rcpFrom, value: r.pickup!),
                if (r.dropoff != null && r.dropoff!.isNotEmpty) AmountRow(label: l10n.rcpTo, value: r.dropoff!),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Card(
          key: const Key('receipt-breakdown'),
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(l10n.rcpBreakdown, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.xs),
                AmountRow(label: l10n.bkAgreedFare, value: money(r.agreed)),
                for (final a in r.adjustments)
                  AmountRow(label: l10n.rcpAdjustment(a.reason.isEmpty ? a.type : a.reason), value: money(a.amount)),
                if (r.adjustments.isNotEmpty) AmountRow(label: l10n.rcpSubtotal, value: money(r.subtotal)),
                if (r.discount != null && !r.discount!.isZero)
                  AmountRow(label: l10n.rcpDiscount, value: '-${money(r.discount!)}', color: AppColors.success),
                if (r.customerFee != null && !r.customerFee!.isZero) AmountRow(label: l10n.rcpServiceFee, value: money(r.customerFee!)),
                if (r.tax != null && !r.tax!.isZero)
                  AmountRow(
                    label: r.taxRateBp == null ? l10n.rcpTax : l10n.rcpTaxRate((r.taxRateBp! / 100).toStringAsFixed(r.taxRateBp! % 100 == 0 ? 0 : 2)),
                    value: money(r.tax!),
                  ),
                if (r.tip != null && !r.tip!.isZero) AmountRow(label: l10n.rcpTip, value: money(r.tip!)),
                const Divider(),
                if (r.total != null) AmountRow(label: l10n.rcpTotal, value: money(r.total!), bold: true),
                const SizedBox(height: AppSpacing.xs),
                AmountRow(label: l10n.bkReceiptPayment, value: r.paymentMethod == PaymentMethodType.cash ? l10n.bkPaymentCash : l10n.bkPaymentOther),
                AmountRow(label: l10n.bkReceiptPaymentStatus, value: receiptPaymentStatusText(l10n, r.paymentStatus)),
              ],
            ),
          ),
        ),
        if (r.htmlUrl != null) ...[
          const SizedBox(height: AppSpacing.md),
          AppButton(
            key: const Key('receipt-print'),
            label: l10n.rcpOpenPrintable,
            icon: Icons.print_outlined,
            secondary: true,
            onPressed: () => ref.read(gatewayLauncherProvider)(r.htmlUrl!),
          ),
        ],
      ],
    );
  }
}

String receiptPaymentStatusText(AppLocalizations l10n, PaymentStatus s) => switch (s) {
  PaymentStatus.cashDue => l10n.bkPaymentStatusCashDue,
  PaymentStatus.paid || PaymentStatus.cashCollected => l10n.bkPaymentStatusPaid,
  PaymentStatus.pending || PaymentStatus.requiresAction || PaymentStatus.authorized => l10n.bkPaymentStatusPending,
  PaymentStatus.failed => l10n.payStatusFailed,
  PaymentStatus.refunded => l10n.payStatusRefunded,
  PaymentStatus.partiallyRefunded => l10n.payStatusPartRefunded,
  PaymentStatus.notRequired => l10n.payStatusNotRequired,
  PaymentStatus.reconciliationRequired => l10n.payStatusReview,
  PaymentStatus.unknown => l10n.bkPaymentStatusOther,
};
