import 'package:flutter/material.dart';

import '../ui/components.dart';
import '../ui/tokens.dart';
import '../money/money_formatter.dart';
import 'finance_models.dart';
import '../ui/isolated_text.dart';

/// "12 Mar 2026 14:05" in the person's locale.
String formatFinanceTime(BuildContext context, DateTime? value) {
  if (value == null) return '';
  final local = value.toLocal();
  final m = MaterialLocalizations.of(context);
  return '${m.formatMediumDate(local)} ${m.formatTimeOfDay(TimeOfDay.fromDateTime(local))}';
}

/// One wallet statement line. Texts come from the server (already in the
/// person's language); credits are green, debits red.
class WalletLineTile extends StatelessWidget {
  const WalletLineTile({super.key, required this.line, required this.formatter, this.balanceLabel});

  final WalletLine line;
  final MoneyFormatter formatter;

  /// "Balance: {amount}" in the person's language.
  final String Function(String amount)? balanceLabel;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final credit = !line.change.isNegative;
    final color = credit ? AppColors.success : AppColors.danger;
    return ListTile(
      leading: Icon(credit ? Icons.south_west : Icons.north_east, color: color),
      title: Text(line.description.isEmpty ? line.typeLabel : line.description),
      subtitle: Text(
        [
          formatFinanceTime(context, line.postedAt),
          if (balanceLabel != null) balanceLabel!(formatter.format(line.balanceAfter)),
        ].where((s) => s.isNotEmpty).join(' - '),
      ),
      trailing: IsolatedText(
        credit ? '+${formatter.format(line.change)}' : formatter.format(line.change),
        style: theme.textTheme.titleSmall?.copyWith(color: color, fontWeight: FontWeight.w700)),
    );
  }
}

/// Texts of [AdjustmentCard]; each app fills them from its ARB files.
class AdjustmentStrings {
  const AdjustmentStrings({
    required this.approve,
    required this.reject,
    required this.withdraw,
    required this.requestedByYou,
    required this.requestedByOther,
    required this.expires,
    required this.newTotal,
    required this.note,
  });

  final String approve;
  final String reject;
  final String withdraw;
  final String requestedByYou;
  final String requestedByOther;
  final String Function(String time) expires;
  final String Function(String amount) newTotal;
  final String note;
}

/// A fare adjustment: type, amount, reason, the new total and, while it is open,
/// the buttons the server allows (`can_answer` / `can_withdraw`).
class AdjustmentCard extends StatelessWidget {
  const AdjustmentCard({
    super.key,
    required this.adjustment,
    required this.formatter,
    required this.strings,
    this.busy = false,
    this.onApprove,
    this.onReject,
    this.onWithdraw,
    this.detail,
  });

  /// The scope item this change refers to, already translated by the app
  /// (moving / freight bookings); null for other bookings.
  final String? detail;

  final Adjustment adjustment;
  final MoneyFormatter formatter;
  final AdjustmentStrings strings;
  final bool busy;
  final VoidCallback? onApprove;
  final VoidCallback? onReject;
  final VoidCallback? onWithdraw;

  ChipTone get _tone => switch (adjustment.status) {
    'approved' => ChipTone.success,
    'pending' => ChipTone.warning,
    'rejected' || 'expired' || 'voided' => ChipTone.danger,
    _ => ChipTone.neutral,
  };

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text(adjustment.typeLabel, style: theme.textTheme.titleMedium)),
                StatusChip(label: adjustment.statusLabel, tone: _tone),
              ],
            ),
            const SizedBox(height: AppSpacing.xs),
            IsolatedText(
              formatter.format(adjustment.amount),
              style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
            if (detail != null && detail!.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(detail!, key: Key('adjustment-detail-${adjustment.id}'), style: theme.textTheme.labelLarge),
            ],
            if (adjustment.reason.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(adjustment.reason),
            ],
            if (adjustment.newTotal != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(strings.newTotal(formatter.format(adjustment.newTotal!)), style: theme.textTheme.bodySmall),
            ],
            Text(
              adjustment.requestedByMe ? strings.requestedByYou : strings.requestedByOther,
              style: theme.textTheme.bodySmall,
            ),
            if (adjustment.isPending && adjustment.expiresAt != null)
              Text(strings.expires(formatFinanceTime(context, adjustment.expiresAt)), style: theme.textTheme.bodySmall),
            if (adjustment.decisionNote != null && adjustment.decisionNote!.isNotEmpty)
              Text('${strings.note}: ${adjustment.decisionNote}', style: theme.textTheme.bodySmall),
            if (adjustment.canAnswer || adjustment.canWithdraw) ...[
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  if (adjustment.canAnswer) ...[
                    Expanded(
                      child: AppButton(
                        key: Key('adjustment-approve-${adjustment.id}'),
                        label: strings.approve,
                        loading: busy,
                        onPressed: busy ? null : onApprove,
                      ),
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: AppButton(
                        key: Key('adjustment-reject-${adjustment.id}'),
                        label: strings.reject,
                        secondary: true,
                        onPressed: busy ? null : onReject,
                      ),
                    ),
                  ],
                  if (adjustment.canWithdraw)
                    Expanded(
                      child: AppButton(
                        key: Key('adjustment-withdraw-${adjustment.id}'),
                        label: strings.withdraw,
                        secondary: true,
                        loading: busy,
                        onPressed: busy ? null : onWithdraw,
                      ),
                    ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// Label/value row used by the receipt and the earnings breakdown.
class AmountRow extends StatelessWidget {
  const AmountRow({super.key, required this.label, required this.value, this.bold = false, this.color});

  final String label;
  final String value;
  final bool bold;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final style = (bold ? theme.textTheme.titleMedium : theme.textTheme.bodyMedium)?.copyWith(color: color);
    return Padding(
      padding: const EdgeInsetsDirectional.symmetric(vertical: 3),
      child: Row(
        children: [
          Expanded(child: Text(label, style: style)),
          IsolatedText(value, style: style),
        ],
      ),
    );
  }
}
