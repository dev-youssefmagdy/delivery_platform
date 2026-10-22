import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../jobs/domain/amount_input.dart';
import '../../../jobs/domain/entities/job_entities.dart';
import '../../../jobs/presentation/widgets/scope_builder.dart' show ScopeStepper;
import '../providers/earnings_providers.dart';

/// "I collected the cash": the driver confirms the cash of a completed cash
/// job. Offered only while the server allows `confirm_cash_collected`; a retry
/// after a dropped connection reuses the same Idempotency-Key.
class CashCollectedCard extends ConsumerStatefulWidget {
  const CashCollectedCard({super.key, required this.job});

  final Job job;

  @override
  ConsumerState<CashCollectedCard> createState() => _CashCollectedCardState();
}

class _CashCollectedCardState extends ConsumerState<CashCollectedCard> {
  bool _busy = false;
  String? _error;
  bool _done = false;

  Future<void> _confirm() async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(cashActionsProvider).confirm(widget.job.id);
      if (mounted) setState(() => _done = true);
    } on Object catch (e) {
      if (mounted) setState(() => _error = moneyErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final amount = widget.job.finalFare ?? widget.job.agreedFare;
    if (_done) {
      return Card(
        key: const Key('cash-confirmed'),
        child: ListTile(leading: const Icon(Icons.check_circle, color: AppColors.success), title: Text(l10n.cashConfirmedTitle), subtitle: Text(l10n.cashConfirmedBody)),
      );
    }
    return Card(
      key: const Key('cash-card'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.cashCollectTitle, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: AppSpacing.xs),
            Text(amount == null ? l10n.cashCollectBodyNoAmount : l10n.cashCollectBody(f.format(amount))),
            if (_error != null) Padding(padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs), child: Text(_error!, key: const Key('cash-error'), style: TextStyle(color: Theme.of(context).colorScheme.error))),
            const SizedBox(height: AppSpacing.sm),
            AppButton(key: const Key('cash-confirm'), label: l10n.cashConfirm, icon: Icons.payments_outlined, loading: _busy, onPressed: _confirm),
          ],
        ),
      ),
    );
  }
}

/// Scope items an adjustment type may refer to (`config/offer_scope.php`
/// `adjustment_items`); the server stays the authority.
const adjustmentScopeItems = <String, List<String>>{
  'extra_helper': ['helpers'],
  'stairs': ['stairs'],
  'extra_stop': ['extra_stops'],
  'waiting': ['waiting_allowance'],
  'toll_parking': ['tolls', 'parking'],
  'scope_change': [
    'helpers',
    'stairs',
    'extra_stops',
    'waiting_allowance',
    'tolls',
    'parking',
    'packing',
    'assembly',
    'loading_equipment',
  ],
};

String scopeItemText(AppLocalizations l10n, String item) => switch (item) {
  'helpers' => l10n.scHelpers,
  'stairs' => l10n.scStairs,
  'extra_stops' => l10n.scExtraStops,
  'waiting_allowance' => l10n.scWaiting,
  'tolls' => l10n.scTolls,
  'parking' => l10n.scParking,
  'packing' => l10n.scPacking,
  'assembly' => l10n.scAssembly,
  'loading_equipment' => l10n.scEquipment,
  _ => item,
};

/// The scope line of an adjustment card ("Item: Helpers · 2 helpers").
String? adjustmentDetailText(AppLocalizations l10n, Adjustment a) {
  final item = a.scopeItem;
  if (item == null) return null;
  return [
    l10n.dadjScopeItemLine(scopeItemText(l10n, item)),
    if (a.helpers != null) l10n.dadjHelpersLine(a.helpers!),
    if (a.floors != null) l10n.dadjFloorsLine(a.floors!),
  ].join(' · ');
}

AdjustmentStrings _strings(AppLocalizations l10n) => AdjustmentStrings(
  approve: l10n.adjApprove,
  reject: l10n.adjReject,
  withdraw: l10n.adjWithdraw,
  requestedByYou: l10n.dadjRequestedByYou,
  requestedByOther: l10n.dadjRequestedByCustomer,
  expires: l10n.adjExpires,
  newTotal: l10n.adjNewTotal,
  note: l10n.adjNote,
);

/// Fare changes of the job: the driver's own requests (withdraw while open),
/// the customer's requests (approve or reject), and a button to ask for a new
/// one (extra stop, waiting, toll or parking, scope change).
class JobAdjustmentsSection extends ConsumerStatefulWidget {
  const JobAdjustmentsSection({super.key, required this.job});

  final Job job;

  @override
  ConsumerState<JobAdjustmentsSection> createState() => _JobAdjustmentsSectionState();
}

class _JobAdjustmentsSectionState extends ConsumerState<JobAdjustmentsSection> {
  String? _busy;

  Future<void> _run(String id, Future<void> Function() action) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = id);
    try {
      await action();
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(moneyErrorText(l10n, e))));
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final job = widget.job;
    final list = ref.watch(jobAdjustmentsProvider(job.id));
    final notifier = ref.read(jobAdjustmentsProvider(job.id).notifier);
    final items = list.value ?? const <Adjustment>[];
    final canRequest = job.can(JobAction.requestAdjustment);
    if (items.isEmpty && !canRequest) return const SizedBox.shrink();
    return Column(
      key: const Key('job-adjustments'),
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(l10n.dadjTitle, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        if (list.hasError && items.isEmpty)
          Text(moneyErrorText(l10n, list.error!), style: TextStyle(color: Theme.of(context).colorScheme.error)),
        for (final a in items) ...[
          AdjustmentCard(
            adjustment: a,
            detail: adjustmentDetailText(l10n, a),
            formatter: f,
            strings: _strings(l10n),
            busy: _busy == a.id,
            onApprove: () => _run(a.id, () => notifier.answer(a, approve: true)),
            onReject: () => _run(a.id, () => notifier.answer(a, approve: false)),
            onWithdraw: () => _run(a.id, () => notifier.withdraw(a)),
          ),
          const SizedBox(height: AppSpacing.sm),
        ],
        if (canRequest)
          AppButton(
            key: const Key('adjustment-new'),
            label: l10n.dadjRequest,
            icon: Icons.add_circle_outline,
            secondary: true,
            onPressed: () => showModalBottomSheet<void>(
              context: context,
              isScrollControlled: true,
              showDragHandle: true,
              builder: (_) => _NewAdjustmentSheet(bookingId: job.id, scoped: job.scope.agreed != null),
            ),
          ),
      ],
    );
  }
}

class _NewAdjustmentSheet extends ConsumerStatefulWidget {
  const _NewAdjustmentSheet({required this.bookingId, this.scoped = false});

  final String bookingId;

  /// A moving / freight job with an agreed scope: the change must name the
  /// scope item it refers to.
  final bool scoped;

  @override
  ConsumerState<_NewAdjustmentSheet> createState() => _NewAdjustmentSheetState();
}

class _NewAdjustmentSheetState extends ConsumerState<_NewAdjustmentSheet> {
  late AdjustmentType _type = widget.scoped ? AdjustmentType.extraHelper : AdjustmentType.extraStop;
  final _amount = TextEditingController();
  final _reason = TextEditingController();
  final _reference = TextEditingController();
  String? _scopeItem;
  int _helpers = 1;
  int _floors = 1;
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _amount.dispose();
    _reason.dispose();
    _reference.dispose();
    super.dispose();
  }

  String _typeText(AppLocalizations l10n, AdjustmentType t) => switch (t) {
    AdjustmentType.extraStop => l10n.dadjTypeExtraStop,
    AdjustmentType.waiting => l10n.dadjTypeWaiting,
    AdjustmentType.tollParking => l10n.dadjTypeToll,
    AdjustmentType.extraHelper => l10n.dadjTypeHelper,
    AdjustmentType.stairs => l10n.dadjTypeStairs,
    _ => l10n.dadjTypeScope,
  };

  List<String> get _items => adjustmentScopeItems[_type.value] ?? const [];

  /// The scope item sent: the chosen one, or the only one the type has.
  String? get _effectiveItem {
    if (!widget.scoped) return null;
    if (_items.length == 1) return _items.first;
    return _items.contains(_scopeItem) ? _scopeItem : null;
  }

  Future<void> _send() async {
    final l10n = AppLocalizations.of(context);
    final navigator = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final exponent = ref.read(appConfigProvider).value?.currencyExponent ?? 2;
    final amount = _amount.text.trim().isEmpty ? null : parseAmountMinor(_amount.text, exponent: exponent);
    if (_amount.text.trim().isNotEmpty && (amount == null || amount <= 0)) {
      setState(() => _error = l10n.dadjErrAmount);
      return;
    }
    if (amount == null && _type != AdjustmentType.waiting) {
      setState(() => _error = l10n.dadjErrAmount);
      return;
    }
    if (_reason.text.trim().length < 3) {
      setState(() => _error = l10n.dadjErrReason);
      return;
    }
    if (widget.scoped && _effectiveItem == null) {
      setState(() => _error = l10n.dadjErrScopeItem);
      return;
    }
    if (_type == AdjustmentType.tollParking && _reference.text.trim().isEmpty) {
      setState(() => _error = l10n.dadjErrEvidence);
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(jobAdjustmentsProvider(widget.bookingId).notifier).create(NewAdjustmentInput(
          type: _type,
          reason: _reason.text.trim(),
          amountMinor: amount,
          scopeItem: _effectiveItem,
          helpers: _type == AdjustmentType.extraHelper ? _helpers : null,
          floors: _type == AdjustmentType.stairs ? _floors : null,
          evidenceReference: _type == AdjustmentType.tollParking ? _reference.text.trim() : null,
        ));
      messenger.showSnackBar(SnackBar(content: Text(l10n.dadjSent)));
      if (navigator.mounted) navigator.pop();
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = moneyErrorText(l10n, e);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Padding(
      padding: EdgeInsetsDirectional.only(start: AppSpacing.md, end: AppSpacing.md, bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.md),
      child: SingleChildScrollView(
        child: Column(
          key: const Key('adjustment-form'),
          crossAxisAlignment: CrossAxisAlignment.stretch,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(l10n.dadjRequest, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              children: [
                for (final t in widget.scoped ? AdjustmentType.scopedChoices : AdjustmentType.choices)
                  ChoiceChip(key: Key('adj-type-${t.value}'), label: Text(_typeText(l10n, t)), selected: _type == t, onSelected: (_) => setState(() => _type = t)),
              ],
            ),
            if (widget.scoped && _items.length > 1) ...[
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  for (final item in _items)
                    ChoiceChip(
                      key: Key('adj-item-$item'),
                      label: Text(scopeItemText(l10n, item)),
                      selected: _scopeItem == item,
                      onSelected: (_) => setState(() => _scopeItem = item),
                    ),
                ],
              ),
            ],
            if (_type == AdjustmentType.extraHelper)
              ScopeStepper(
                key: const ValueKey('adj-helpers'),
                label: l10n.dadjHelpersCount,
                value: _helpers,
                min: 1,
                max: 5,
                onChanged: (v) => setState(() => _helpers = v),
              ),
            if (_type == AdjustmentType.stairs)
              ScopeStepper(
                key: const ValueKey('adj-floors'),
                label: l10n.dadjFloorsCount,
                value: _floors,
                min: 1,
                max: 40,
                onChanged: (v) => setState(() => _floors = v),
              ),
            if (_type == AdjustmentType.tollParking)
              TextField(
                key: const Key('adj-reference'),
                controller: _reference,
                maxLength: 120,
                decoration: InputDecoration(labelText: l10n.dadjReceiptRef),
              ),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              key: const Key('adj-amount'),
              controller: _amount,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
              textDirection: TextDirection.ltr,
              decoration: InputDecoration(labelText: l10n.dadjAmount, helperText: _type == AdjustmentType.waiting ? l10n.dadjWaitingHelp : null),
            ),
            const SizedBox(height: AppSpacing.sm),
            TextField(key: const Key('adj-reason'), controller: _reason, maxLength: 200, decoration: InputDecoration(labelText: l10n.dadjReason)),
            Text(l10n.dadjCustomerApproves, style: Theme.of(context).textTheme.bodySmall),
            if (_error != null) Padding(padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs), child: Text(_error!, key: const Key('adj-error'), style: TextStyle(color: Theme.of(context).colorScheme.error))),
            const SizedBox(height: AppSpacing.sm),
            AppButton(key: const Key('adj-send'), label: l10n.dadjSend, loading: _busy, onPressed: _send),
          ],
        ),
      ),
    );
  }
}
