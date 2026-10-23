import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../payments/presentation/providers/payments_providers.dart';
import '../providers/booking_providers.dart';

/// Payment method choice of the booking form: cash plus the online methods the
/// service accepts (card, mobile wallet). Online payment happens after the trip.
class PaymentChooser extends ConsumerWidget {
  const PaymentChooser({super.key, required this.category});

  final ServiceCategoryInfo category;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final draft = ref.watch(bookingDraftProvider);
    final accepted = category.paymentMethods.isEmpty
        ? const [PaymentMethodType.cash]
        : category.paymentMethods;
    final options = [
      for (final m in const [
        PaymentMethodType.cash,
        PaymentMethodType.card,
        PaymentMethodType.mobileWallet,
      ])
        if (accepted.contains(m)) m,
    ];
    if (options.isEmpty) options.add(PaymentMethodType.cash);
    final selected = options.contains(draft.payment)
        ? draft.payment
        : options.first;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          l10n.bkPaymentTitle,
          style: Theme.of(context).textTheme.titleMedium,
        ),
        RadioGroup<PaymentMethodType>(
          groupValue: selected,
          onChanged: (v) {
            if (v != null) {
              ref.read(bookingDraftProvider.notifier).setPayment(v);
            }
          },
          child: Column(
            children: [
              for (final m in options)
                RadioListTile<PaymentMethodType>(
                  key: Key('payment-${m.value}'),
                  contentPadding: EdgeInsets.zero,
                  value: m,
                  secondary: Icon(switch (m) {
                    PaymentMethodType.card => Icons.credit_card,
                    PaymentMethodType.mobileWallet => Icons.phone_android,
                    _ => Icons.payments_outlined,
                  }),
                  title: Text(switch (m) {
                    PaymentMethodType.card => l10n.pmCard,
                    PaymentMethodType.mobileWallet => l10n.pmWallet,
                    _ => l10n.bkPaymentCash,
                  }),
                  subtitle: m == PaymentMethodType.cash
                      ? null
                      : Text(l10n.bkPaymentOnlineNote),
                ),
            ],
          ),
        ),
      ],
    );
  }
}

/// Coupon code box of the booking form. "Apply" asks the server for a quote
/// (nothing is reserved) and shows the discount or the explanation of the
/// refusal. The booking request carries only the code; the server prices it.
class CouponBox extends ConsumerStatefulWidget {
  const CouponBox({super.key, required this.category, this.lat, this.lng});

  final ServiceCategoryInfo category;
  final double? lat;
  final double? lng;

  @override
  ConsumerState<CouponBox> createState() => _CouponBoxState();
}

class _CouponBoxState extends ConsumerState<CouponBox> {
  final _controller = TextEditingController();

  @override
  void initState() {
    super.initState();
    _controller.text = ref.read(couponCheckProvider).code;
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final check = ref.watch(couponCheckProvider);
    final draft = ref.watch(bookingDraftProvider);
    final formatter = ref.watch(moneyFormatterProvider);
    final language = Localizations.localeOf(context).languageCode;
    final fare = draft.fareMinor;
    final quote = check.quote;
    final canApply = check.code.isNotEmpty && fare != null && !check.checking;

    Widget? result;
    if (check.checking) {
      result = const Skeleton(height: 14, width: 180);
    } else if (quote != null && quote.valid) {
      result = Row(
        key: const Key('coupon-valid'),
        children: [
          const Icon(Icons.check_circle, color: AppColors.success, size: 20),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(
              l10n.cpnQuoteDiscount(
                formatter.format(quote.discount),
                quote.coupon?.title.resolveOrNull(language) ?? check.code,
              ),
              style: theme.textTheme.bodyMedium?.copyWith(
                color: AppColors.success,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      );
    } else if (quote != null) {
      result = Row(
        key: const Key('coupon-invalid'),
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline, color: theme.colorScheme.error, size: 20),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(
              couponReasonText(l10n, quote.reasonCode, fallback: quote.message),
              style: TextStyle(color: theme.colorScheme.error),
            ),
          ),
        ],
      );
    } else if (check.error != null) {
      result = Text(
        key: const Key('coupon-error'),
        paymentErrorText(l10n, check.error!),
        style: TextStyle(color: theme.colorScheme.error),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(l10n.cpnFormTitle, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: TextField(
                key: const Key('coupon-field'),
                controller: _controller,
                textCapitalization: TextCapitalization.characters,
                textDirection: TextDirection.ltr,
                maxLength: 40,
                onChanged: (v) =>
                    ref.read(couponCheckProvider.notifier).setCode(v),
                decoration: InputDecoration(
                  labelText: l10n.cpnFormLabel,
                  counterText: '',
                ),
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            Padding(
              padding: const EdgeInsetsDirectional.only(top: 4),
              child: FilledButton.tonal(
                key: const Key('coupon-apply'),
                onPressed: canApply
                    ? () => ref
                          .read(couponCheckProvider.notifier)
                          .check(
                            serviceCategoryId: widget.category.id,
                            fareMinor: fare,
                            paymentMethod: draft.payment,
                            lat: widget.lat,
                            lng: widget.lng,
                          )
                    : null,
                child: Text(l10n.cpnApply),
              ),
            ),
          ],
        ),
        if (check.code.isNotEmpty && fare == null)
          Padding(
            padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
            child: Text(l10n.cpnNeedFare, style: theme.textTheme.bodySmall),
          ),
        if (result != null)
          Padding(
            padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
            child: result,
          ),
        if (check.code.isNotEmpty &&
            quote == null &&
            !check.checking &&
            check.error == null &&
            fare != null)
          Padding(
            padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
            child: Text(l10n.cpnNotChecked, style: theme.textTheme.bodySmall),
          ),
      ],
    );
  }
}
