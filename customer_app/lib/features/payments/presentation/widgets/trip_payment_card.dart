import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../booking/domain/entities/booking_entities.dart';
import '../providers/payments_providers.dart';
import 'payment_flow_panel.dart';

/// "Payment due" for a finished card / wallet trip: the amount the server
/// computed (after any coupon) and the payment flow. Cash trips and trips a
/// coupon fully covered never show it.
class TripPaymentCard extends ConsumerStatefulWidget {
  const TripPaymentCard({super.key, required this.booking});

  final Booking booking;

  /// Whether the booking needs the card at all.
  static bool needed(Booking booking) {
    final online = booking.paymentMethod == PaymentMethodType.card || booking.paymentMethod == PaymentMethodType.mobileWallet;
    if (!online) return false;
    if (booking.can(BookingAction.pay)) return true;
    return booking.status == BookingStatus.completedPendingPayment &&
        const {PaymentStatus.pending, PaymentStatus.requiresAction, PaymentStatus.failed, PaymentStatus.reconciliationRequired}.contains(booking.paymentStatus);
  }

  @override
  ConsumerState<TripPaymentCard> createState() => _TripPaymentCardState();
}

class _TripPaymentCardState extends ConsumerState<TripPaymentCard> {
  @override
  void initState() {
    super.initState();
    // The app was closed during an attempt: ask the server what became of it
    // (the same open attempt is returned; the gateway page is not reopened).
    final status = widget.booking.paymentStatus;
    if (status == PaymentStatus.pending || status == PaymentStatus.requiresAction || status == PaymentStatus.reconciliationRequired) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        final pay = PayWith(method: widget.booking.paymentMethod);
        ref.read(paymentFlowProvider(PayArgs.trip(widget.booking.id)).notifier).start(pay, openGateway: false);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final booking = widget.booking;
    final formatter = ref.watch(moneyFormatterProvider);
    final receipt = booking.receipt;
    final due = receipt?.customerPayable ?? booking.finalFare ?? booking.agreedFare;
    return Card(
      key: const Key('trip-payment-card'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.payDueTitle, style: theme.textTheme.titleMedium),
            if (due != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(
                formatter.format(due),
                key: const Key('pay-amount'),
                textDirection: TextDirection.ltr,
                style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
              ),
            ],
            if (receipt != null && receipt.couponApplied && receipt.discount != null && !receipt.discount!.isZero)
              Text(l10n.payCouponSaved(formatter.format(receipt.discount!)), key: const Key('pay-coupon-line'), style: theme.textTheme.bodyMedium),
            const SizedBox(height: AppSpacing.md),
            PaymentFlowPanel(
              args: PayArgs.trip(booking.id),
              method: booking.paymentMethod,
              payLabel: l10n.payNow,
              bookingId: booking.id,
              bookingCode: booking.code,
            ),
          ],
        ),
      ),
    );
  }
}

/// Optional tip after the trip. Nothing is preselected: the person picks an
/// amount, then pays it like any other online payment. The server bounds it.
class TipCard extends ConsumerStatefulWidget {
  const TipCard({super.key, required this.booking});

  final Booking booking;

  @override
  ConsumerState<TipCard> createState() => _TipCardState();
}

class _TipCardState extends ConsumerState<TipCard> {
  int? _tipMinor;
  bool _custom = false;
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  /// Round, not preselected suggestions: 5%, 10% and 15% of the fare, rounded to whole units.
  List<int> _presets(Money fare) {
    final unit = _unit(fare.exponent);
    final out = <int>{};
    for (final pct in const [5, 10, 15]) {
      final minor = ((fare.minor * pct / 100) / unit).round() * unit;
      if (minor > 0) out.add(minor);
    }
    return out.toList()..sort();
  }

  int _unit(int exponent) {
    var f = 1;
    for (var i = 0; i < exponent; i++) {
      f *= 10;
    }
    return f;
  }

  void _onCustom(String text, int exponent) {
    final cleaned = text.replaceAll(RegExp('[^0-9]'), '');
    final whole = int.tryParse(cleaned);
    setState(() => _tipMinor = whole == null || whole == 0 ? null : whole * _unit(exponent));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final booking = widget.booking;
    final formatter = ref.watch(moneyFormatterProvider);
    final fare = booking.finalFare ?? booking.agreedFare;
    if (fare == null) return const SizedBox.shrink();
    final presets = _presets(fare);
    final tip = _tipMinor;
    return Card(
      key: const Key('tip-card'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.tipTitle, style: theme.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.xs),
            Text(l10n.tipBody, style: theme.textTheme.bodyMedium),
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.sm,
              children: [
                for (final minor in presets)
                  ChoiceChip(
                    key: Key('tip-$minor'),
                    label: Text(formatter.format(Money(minor: minor, currency: fare.currency, exponent: fare.exponent))),
                    selected: !_custom && tip == minor,
                    onSelected: (_) => setState(() {
                      _custom = false;
                      _tipMinor = minor;
                    }),
                  ),
                ChoiceChip(
                  key: const Key('tip-custom'),
                  label: Text(l10n.tipOther),
                  selected: _custom,
                  onSelected: (_) => setState(() {
                    _custom = true;
                    _tipMinor = null;
                    _controller.clear();
                  }),
                ),
              ],
            ),
            if (_custom) ...[
              const SizedBox(height: AppSpacing.sm),
              TextField(
                key: const Key('tip-amount-field'),
                controller: _controller,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                onChanged: (t) => _onCustom(t, fare.exponent),
                decoration: InputDecoration(labelText: l10n.tipAmountLabel(fare.currency)),
              ),
            ],
            if (tip != null) ...[
              const SizedBox(height: AppSpacing.md),
              PaymentFlowPanel(
                key: ValueKey('tip-flow-$tip'),
                args: PayArgs.tip(booking.id, tip),
                method: PaymentMethodType.card,
                payLabel: l10n.tipPay(formatter.format(Money(minor: tip, currency: fare.currency, exponent: fare.exponent))),
                bookingId: booking.id,
                bookingCode: booking.code,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
