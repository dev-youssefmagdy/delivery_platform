import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../booking/presentation/widgets/delivery_texts.dart' show scopeItemText;
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
/// The scope line of an adjustment card ("Agreement item: Helpers · 2 helpers").
String? adjustmentDetailText(AppLocalizations l10n, Adjustment a) {
  final item = a.scopeItem;
  if (item == null) return null;
  return [
    l10n.adjScopeItemLine(scopeItemText(l10n, item)),
    if (a.helpers != null) l10n.adjHelpersLine(a.helpers!),
    if (a.floors != null) l10n.adjFloorsLine(a.floors!),
  ].join(' · ');
}

class AdjustmentsSection extends ConsumerStatefulWidget {
  const AdjustmentsSection({super.key, required this.bookingId, this.scoped = false, this.canRequest = false});

  final String bookingId;

  /// A moving / freight booking with an agreed scope: changes name the scope
  /// item they refer to.
  final bool scoped;

  /// The server allows `request_adjustment` right now.
  final bool canRequest;

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
    final showRequest = widget.scoped && widget.canRequest;
    if ((list == null || list.isEmpty) && !showRequest) return const SizedBox.shrink();
    final strings = adjustmentStrings(l10n);
    return Column(
      key: const Key('adjustments-section'),
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(l10n.adjTitle, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        if (showRequest) ...[
          AppButton(
            key: const Key('adjustment-request'),
            label: l10n.adjRequestButton,
            icon: Icons.add_circle_outline,
            secondary: true,
            onPressed: () => showModalBottomSheet<void>(
              context: context,
              isScrollControlled: true,
              showDragHandle: true,
              builder: (_) => _RequestSheet(bookingId: widget.bookingId),
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
        ],
        for (final a in list ?? const <Adjustment>[]) ...[
          AdjustmentCard(
            adjustment: a,
            detail: adjustmentDetailText(l10n, a),
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
            if (adjustmentDetailText(l10n, a) != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                child: Text(adjustmentDetailText(l10n, a)!, key: const Key('adjustment-sheet-scope'), style: theme.textTheme.labelLarge),
              ),
            const SizedBox(height: AppSpacing.md),
            IsolatedText(
              formatter.format(a.amount),
              style: theme.textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w700)),
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


/// The customer asks for a change on a moving / freight booking: an extra
/// helper or a scope change, naming the item of the agreed scope. The server
/// decides whether it is allowed and the provider answers it.
class _RequestSheet extends ConsumerStatefulWidget {
  const _RequestSheet({required this.bookingId});

  final String bookingId;

  @override
  ConsumerState<_RequestSheet> createState() => _RequestSheetState();
}

class _RequestSheetState extends ConsumerState<_RequestSheet> {
  static const _types = [AdjustmentType.extraHelper, AdjustmentType.scopeChange];
  static const _items = {
    AdjustmentType.extraHelper: ['helpers'],
    AdjustmentType.scopeChange: ['helpers', 'stairs', 'extra_stops', 'waiting_allowance', 'packing', 'assembly', 'loading_equipment'],
  };

  AdjustmentType _type = AdjustmentType.extraHelper;
  String? _item;
  int _helpers = 1;
  final _amount = TextEditingController();
  final _reason = TextEditingController();
  bool _busy = false;
  Object? _error;
  String? _local;

  @override
  void dispose() {
    _amount.dispose();
    _reason.dispose();
    super.dispose();
  }

  String? get _effectiveItem {
    final items = _items[_type]!;
    if (items.length == 1) return items.first;
    return items.contains(_item) ? _item : null;
  }

  Future<void> _send() async {
    final l10n = AppLocalizations.of(context);
    final exponent = ref.read(appConfigProvider).value?.currencyExponent ?? 2;
    final amount = parseAmountMinorText(_amount.text, exponent: exponent);
    if (amount == null || amount <= 0) {
      setState(() => _local = l10n.adjErrAmount);
      return;
    }
    if (_effectiveItem == null) {
      setState(() => _local = l10n.adjErrScopeItem);
      return;
    }
    if (_reason.text.trim().length < 3) {
      setState(() => _local = l10n.adjErrReason);
      return;
    }
    final navigator = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() {
      _busy = true;
      _error = null;
      _local = null;
    });
    try {
      await ref.read(adjustmentsProvider(widget.bookingId).notifier).request(
        NewAdjustmentInput(
          type: _type,
          reason: _reason.text.trim(),
          amountMinor: amount,
          scopeItem: _effectiveItem,
          helpers: _type == AdjustmentType.extraHelper ? _helpers : null,
        ),
      );
      messenger.showSnackBar(SnackBar(content: Text(l10n.adjRequestSent)));
      if (navigator.mounted) navigator.pop();
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = e;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final items = _items[_type]!;
    return Padding(
      padding: EdgeInsetsDirectional.only(
        start: AppSpacing.md,
        end: AppSpacing.md,
        bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.md,
      ),
      child: SingleChildScrollView(
        child: Column(
          key: const Key('adjustment-request-sheet'),
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.adjRequestButton, style: theme.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              children: [
                for (final t in _types)
                  ChoiceChip(
                    key: Key('adj-type-${t.value}'),
                    label: Text(t == AdjustmentType.extraHelper ? l10n.adjTypeHelper : l10n.adjTypeScope),
                    selected: _type == t,
                    onSelected: (_) => setState(() {
                      _type = t;
                      _item = null;
                    }),
                  ),
              ],
            ),
            if (items.length > 1) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(l10n.adjPickItem, style: theme.textTheme.labelLarge),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  for (final item in items)
                    ChoiceChip(
                      key: Key('adj-item-$item'),
                      label: Text(scopeItemText(l10n, item)),
                      selected: _item == item,
                      onSelected: (_) => setState(() => _item = item),
                    ),
                ],
              ),
            ],
            if (_type == AdjustmentType.extraHelper)
              Row(
                children: [
                  Expanded(child: Text(l10n.adjHelpersCount, style: theme.textTheme.titleSmall)),
                  IconButton(
                    key: const Key('adj-helpers-minus'),
                    tooltip: '${l10n.adjHelpersCount} -',
                    onPressed: _helpers > 1 ? () => setState(() => _helpers--) : null,
                    icon: const Icon(Icons.remove_circle_outline),
                  ),
                  Text('$_helpers', key: const Key('adj-helpers-value'), style: theme.textTheme.titleMedium),
                  IconButton(
                    key: const Key('adj-helpers-plus'),
                    tooltip: '${l10n.adjHelpersCount} +',
                    onPressed: _helpers < 5 ? () => setState(() => _helpers++) : null,
                    icon: const Icon(Icons.add_circle_outline),
                  ),
                ],
              ),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              key: const Key('adj-amount'),
              controller: _amount,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              textDirection: TextDirection.ltr,
              decoration: InputDecoration(labelText: l10n.adjAmountLabel),
            ),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              key: const Key('adj-reason'),
              controller: _reason,
              maxLength: 200,
              decoration: InputDecoration(labelText: l10n.adjReasonLabel),
            ),
            if (_local != null || _error != null)
              Text(
                _local ?? paymentErrorText(l10n, _error!),
                key: const Key('adj-error'),
                style: TextStyle(color: theme.colorScheme.error),
              ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(key: const Key('adj-send'), label: l10n.adjSend, loading: _busy, onPressed: _send),
          ],
        ),
      ),
    );
  }
}

/// Typed amount (Western or Arabic digits, `.` or `,` as decimal) to minor
/// units; null when it is not a number with at most [exponent] decimals.
int? parseAmountMinorText(String input, {int exponent = 2}) {
  final number = FormLogic.parseNumber(input);
  if (number == null) return null;
  var factor = 1;
  for (var i = 0; i < exponent; i++) {
    factor *= 10;
  }
  final scaled = number * factor;
  return (scaled - scaled.round()).abs() < 1e-9 ? scaled.round() : null;
}
