import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../providers/earnings_providers.dart';

/// The immutable receipt of a finished job: the fare lines and the driver's
/// earnings (commission, discounts, net). The customer's fee and tax never appear.
class JobReceiptScreen extends ConsumerWidget {
  const JobReceiptScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final receipt = ref.watch(jobReceiptProvider(bookingId));
    return Scaffold(
      appBar: AppBar(title: Text(l10n.rcpTitle)),
      body: SafeArea(
        child: AsyncStateView<TripReceipt>(
          value: receipt,
          onRetry: () => ref.invalidate(jobReceiptProvider(bookingId)),
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, r) {
            String m(Money v) => f.format(v);
            final e = r.earnings;
            return ListView(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              children: [
                Text(l10n.rcpNumber(r.number), key: const Key('receipt-number'), style: Theme.of(context).textTheme.titleMedium),
                if (r.counterpartName != null) AmountRow(label: l10n.rcpCustomer, value: r.counterpartName!),
                AmountRow(label: l10n.jbReceiptCode, value: r.bookingCode),
                const Divider(),
                AmountRow(label: l10n.rcpAgreed, value: m(r.agreed)),
                for (final a in r.adjustments) AmountRow(label: l10n.rcpAdjustment(a.reason.isEmpty ? a.type : a.reason), value: m(a.amount)),
                AmountRow(label: l10n.rcpSubtotal, value: m(r.subtotal), bold: true),
                if (e != null) ...[
                  const Divider(),
                  AmountRow(label: l10n.ernGross, value: m(e.gross)),
                  AmountRow(label: l10n.ernCommission, value: '-${m(e.commission)}'),
                  if (!e.discountProvider.isZero) AmountRow(label: l10n.ernProviderDiscount, value: '-${m(e.discountProvider)}'),
                  AmountRow(label: l10n.ernNet, value: m(e.net)),
                  AmountRow(label: l10n.ernEarned, value: m(e.earning), bold: true),
                ],
                if (r.htmlUrl != null) ...[
                  const SizedBox(height: AppSpacing.md),
                  AppButton(
                    key: const Key('receipt-print'),
                    label: l10n.rcpOpenPrintable,
                    secondary: true,
                    onPressed: () => launchUrl(Uri.parse(r.htmlUrl!), mode: LaunchMode.externalApplication),
                  ),
                ],
              ],
            );
          },
        ),
      ),
    );
  }
}
