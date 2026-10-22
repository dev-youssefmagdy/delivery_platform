import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../providers/payments_providers.dart';

AdjustmentStrings adjustmentStrings(AppLocalizations l10n) => AdjustmentStrings(
  approve: l10n.adjApprove,
  reject: l10n.adjReject,
  withdraw: l10n.adjWithdraw,
  requestedByYou: l10n.adjRequestedByYou,
  requestedByOther: l10n.adjRequestedByDriver,
  expires: l10n.adjExpires,
  newTotal: l10n.adjNewTotal,
  note: l10n.adjNote,
);

/// Fare changes the driver asked for during the trip. A pending one that the
/// customer can answer opens an approval sheet once; the list stays below.
/// The total only changes when the customer approves (the server decides).
class AdjustmentsSection extends ConsumerStatefulWidget {
  const AdjustmentsSection({super.key, required this.bookingId});

  final String bookingId;

  @override
  ConsumerState<AdjustmentsSection> createState() => _AdjustmentsSectionState();
}

class _AdjustmentsSectionState extends ConsumerState<AdjustmentsSection> {
  final _shown = <String>{};
  String? _busy;

  Future<void> _answer(Adjustment a, {required bool approve, String? note}) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = a.id);
    try {
      await ref.read(adjustmentsProvider(widget.bookingId).notifier).answer(a, approve: approve, note: note);
      messenger.showSnackBar(SnackBar(content: Text(approve ? l10n.adjApproved : l10n.adjRejected)));
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(paymentErrorText(l10n, e))));
      if (e is ApiException && (e.code == 'adjustment_not_pending' || e.code == 'adjustment_expired')) {
        unawaited(ref.read(adjustmentsProvider(widget.bookingId).notifier).refresh());
      }
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  void _maybeOpenSheet(List<Adjustment> list) {
    for (final a in list) {
      if (a.canAnswer && _shown.add(a.id)) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) showAdjustmentSheet(context, a, onAnswer: (approve, note) => _answer(a, approve: approve, note: note));
        });
        break;
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final adjustments = ref.watch(adjustmentsProvider(widget.bookingId));
    ref.listen(adjustmentsProvider(widget.bookingId), (_, next) {
      final list = next.value;
      if (list != null) _maybeOpenSheet(list);
    });
    final list = adjustments.value;
    if (list == null || list.isEmpty) return const SizedBox.shrink();
    final strings = adjustmentStrings(l10n);
    return Column(
      key: const Key('adjustments-section'),
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(l10n.adjTitle, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        for (final a in list) ...[
          AdjustmentCard(
            adjustment: a,
            formatter: formatter,
            strings: strings,
            busy: _busy == a.id,
            onApprove: () => _answer(a, approve: true),
            onReject: () => _answer(a, approve: false),
          ),
          const SizedBox(height: AppSpacing.sm),
        ],
      ],
    );
  }
}

/// The approval sheet: what is asked, why, and what the total becomes.
Future<void> showAdjustmentSheet(
  BuildContext context,
  Adjustment adjustment, {
  required Future<void> Function(bool approve, String? note) onAnswer,
}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (_) => _AdjustmentSheet(adjustment: adjustment, onAnswer: onAnswer),
  );
}

class _AdjustmentSheet extends ConsumerStatefulWidget {
  const _AdjustmentSheet({required this.adjustment, required this.onAnswer});

  final Adjustment adjustment;
  final Future<void> Function(bool approve, String? note) onAnswer;

  @override
  ConsumerState<_AdjustmentSheet> createState() => _AdjustmentSheetState();
}

class _AdjustmentSheetState extends ConsumerState<_AdjustmentSheet> {
  final _note = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  Future<void> _go(bool approve) async {
    setState(() => _busy = true);
    final navigator = Navigator.of(context);
    await widget.onAnswer(approve, _note.text.trim().isEmpty ? null : _note.text.trim());
    if (navigator.mounted) navigator.pop();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final a = widget.adjustment;
    return Padding(
      padding: EdgeInsetsDirectional.only(
        start: AppSpacing.md,
        end: AppSpacing.md,
        bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.md,
      ),
      child: SingleChildScrollView(
        child: Column(
          key: const Key('adjustment-sheet'),
          crossAxisAlignment: CrossAxisAlignment.stretch,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(l10n.adjSheetTitle, style: theme.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.xs),
            Text(l10n.adjSheetBody(a.typeLabel), style: theme.textTheme.bodyMedium),
            const SizedBox(height: AppSpacing.md),
            Text(
              formatter.format(a.amount),
              textDirection: TextDirection.ltr,
              style: theme.textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            if (a.reason.isNotEmpty) Padding(padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs), child: Text(a.reason)),
            if (a.oldTotal != null && a.newTotal != null) ...[
              const SizedBox(height: AppSpacing.sm),
              AmountRow(label: l10n.adjOldTotal, value: formatter.format(a.oldTotal!)),
              AmountRow(label: l10n.adjNewTotalLabel, value: formatter.format(a.newTotal!), bold: true),
            ],
            const SizedBox(height: AppSpacing.md),
            TextField(
              key: const Key('adjustment-note'),
              controller: _note,
              maxLength: 200,
              decoration: InputDecoration(labelText: l10n.adjNoteOptional),
            ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(key: const Key('adjustment-sheet-approve'), label: l10n.adjApprove, loading: _busy, onPressed: () => _go(true)),
            const SizedBox(height: AppSpacing.sm),
            AppButton(key: const Key('adjustment-sheet-reject'), label: l10n.adjReject, secondary: true, onPressed: _busy ? null : () => _go(false)),
            AppButton(key: const Key('adjustment-sheet-later'), label: l10n.adjDecideLater, secondary: true, onPressed: _busy ? null : () => Navigator.of(context).pop()),
          ],
        ),
      ),
    );
  }
}
