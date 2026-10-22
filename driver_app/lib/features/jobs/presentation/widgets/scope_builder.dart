import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

String scopePackingText(AppLocalizations l10n, String v) => switch (v) {
  'none' => l10n.scPackingNone,
  'wrap_only' => l10n.scPackingWrap,
  'full_pack' => l10n.scPackingFull,
  _ => v,
};

String scopeStairsText(AppLocalizations l10n, String v) => switch (v) {
  'not_needed' => l10n.scStairsNotNeeded,
  'included' => l10n.scStairsIncluded,
  'extra_per_floor' => l10n.scStairsExtra,
  _ => v,
};

String scopeAssemblyText(AppLocalizations l10n, String v) => switch (v) {
  'none' => l10n.scAssemblyNone,
  'disassembly' => l10n.scAssemblyDisassembly,
  'assembly' => l10n.scAssemblyAssembly,
  'both' => l10n.scAssemblyBoth,
  _ => v,
};

String scopeEquipmentText(AppLocalizations l10n, String v) => switch (v) {
  'hand_trolley' => l10n.scEqHandTrolley,
  'forklift' => l10n.scEqForklift,
  'crane' => l10n.scEqCrane,
  'tail_lift' => l10n.scEqTailLift,
  'straps_blankets' => l10n.scEqStraps,
  'pallet_jack' => l10n.scEqPalletJack,
  _ => v,
};

String scopeTreatmentText(AppLocalizations l10n, String v) => switch (v) {
  'included' => l10n.scTreatIncluded,
  'at_cost_with_evidence' => l10n.scTreatAtCost,
  'excluded' => l10n.scTreatExcluded,
  _ => v,
};

String scopeIssueText(AppLocalizations l10n, ScopeIssue issue) => switch (issue) {
  ScopeIssue.required => l10n.scIssueRequired,
  ScopeIssue.outOfRange => l10n.scIssueRange,
  ScopeIssue.invalidChoice => l10n.scIssueChoice,
  ScopeIssue.floorsNeeded => l10n.scIssueFloors,
  ScopeIssue.tooLong => l10n.scIssueTooLong,
  ScopeIssue.duplicate => l10n.scIssueDuplicate,
};

/// Large stepper (56 px buttons) for counts while driving or standing at a
/// truck: minus, the value, plus.
class ScopeStepper extends StatelessWidget {
  const ScopeStepper({
    super.key,
    required this.label,
    required this.value,
    required this.min,
    required this.max,
    required this.onChanged,
    this.step = 1,
    this.suffix,
    this.error,
  });

  final String label;
  final int value;
  final int min;
  final int max;
  final int step;
  final String? suffix;
  final String? error;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final key = (this.key is ValueKey<String>) ? (this.key! as ValueKey<String>).value : label;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.xs),
        Row(
          children: [
            IconButton.filledTonal(
              key: Key('$key-minus'),
              iconSize: 32,
              constraints: const BoxConstraints.tightFor(width: 56, height: 56),
              onPressed: value - step >= min ? () => onChanged(value - step) : null,
              icon: const Icon(Icons.remove),
              tooltip: '-',
            ),
            Expanded(
              child: Text(
                suffix == null ? '$value' : '$value $suffix',
                key: Key('$key-value'),
                textAlign: TextAlign.center,
                style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
              ),
            ),
            IconButton.filledTonal(
              key: Key('$key-plus'),
              iconSize: 32,
              constraints: const BoxConstraints.tightFor(width: 56, height: 56),
              onPressed: value + step <= max ? () => onChanged(value + step) : null,
              icon: const Icon(Icons.add),
              tooltip: '+',
            ),
          ],
        ),
        if (error != null)
          Text(error!, style: TextStyle(color: theme.colorScheme.error)),
      ],
    );
  }
}

/// Single choice as large chips.
class ScopeChoice extends StatelessWidget {
  const ScopeChoice({
    super.key,
    required this.label,
    required this.options,
    required this.selected,
    required this.text,
    required this.onChanged,
    this.error,
  });

  final String label;
  final List<String> options;
  final String selected;
  final String Function(String value) text;
  final ValueChanged<String> onChanged;
  final String? error;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final key = (this.key is ValueKey<String>) ? (this.key! as ValueKey<String>).value : label;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.xs),
        Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            for (final o in options)
              ChoiceChip(
                key: Key('$key-$o'),
                label: Text(text(o), style: theme.textTheme.titleSmall),
                materialTapTargetSize: MaterialTapTargetSize.padded,
                padding: const EdgeInsetsDirectional.symmetric(
                  horizontal: AppSpacing.sm,
                  vertical: AppSpacing.sm,
                ),
                selected: selected == o,
                onSelected: (_) => onChanged(o),
              ),
          ],
        ),
        if (error != null) Text(error!, style: TextStyle(color: theme.colorScheme.error)),
      ],
    );
  }
}

/// The scope builder of a counter-offer (SRS 7.4): everything a moving /
/// freight offer promises, edited with steppers and chips. The options and
/// bounds come from the server's template; [issues] shows the client check and
/// the server's own refusal (`meta.scope_errors`) on the same fields.
class ScopeBuilder extends StatelessWidget {
  const ScopeBuilder({
    super.key,
    required this.scope,
    required this.template,
    required this.onChanged,
    this.issues = const {},
  });

  final OfferScope scope;
  final ScopeTemplate template;
  final ValueChanged<OfferScope> onChanged;
  final Map<String, ScopeIssue> issues;

  String? _err(AppLocalizations l10n, String path) {
    final issue = issues[path] ??
        issues.entries.where((e) => e.key.startsWith('$path.')).map((e) => e.value).firstOrNull;
    return issue == null ? null : scopeIssueText(l10n, issue);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    const gap = SizedBox(height: AppSpacing.md);
    final stairsNeeded = scope.stairsHandling != 'not_needed';
    return Column(
      key: const Key('scope-builder'),
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (_err(l10n, 'vehicle_class') != null)
          Text(
            _err(l10n, 'vehicle_class')!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ScopeStepper(
          key: const ValueKey('scope-helpers'),
          label: l10n.scHelpers,
          value: scope.helpers,
          min: 0,
          max: template.helpersMax,
          error: _err(l10n, 'helpers'),
          onChanged: (v) => onChanged(scope.copyWith(helpers: v)),
        ),
        gap,
        ScopeChoice(
          key: const ValueKey('scope-packing'),
          label: l10n.scPacking,
          options: template.packingOptions,
          selected: scope.packing,
          text: (v) => scopePackingText(l10n, v),
          error: _err(l10n, 'packing'),
          onChanged: (v) => onChanged(scope.copyWith(packing: v)),
        ),
        gap,
        ScopeChoice(
          key: const ValueKey('scope-stairs'),
          label: l10n.scStairs,
          options: template.stairsOptions,
          selected: scope.stairsHandling,
          text: (v) => scopeStairsText(l10n, v),
          error: _err(l10n, 'stairs.handling'),
          onChanged: (v) => onChanged(scope.copyWith(stairsHandling: v)),
        ),
        if (stairsNeeded) ...[
          const SizedBox(height: AppSpacing.sm),
          ScopeStepper(
            key: const ValueKey('scope-floors'),
            label: l10n.scIncludedFloors,
            value: scope.includedFloors,
            min: 0,
            max: template.floorsMax,
            error: _err(l10n, 'stairs.included_floors'),
            onChanged: (v) => onChanged(scope.copyWith(includedFloors: v)),
          ),
        ],
        gap,
        ScopeChoice(
          key: const ValueKey('scope-assembly'),
          label: l10n.scAssembly,
          options: template.assemblyOptions,
          selected: scope.assembly,
          text: (v) => scopeAssemblyText(l10n, v),
          error: _err(l10n, 'assembly'),
          onChanged: (v) => onChanged(scope.copyWith(assembly: v)),
        ),
        gap,
        Text(l10n.scEquipment, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: AppSpacing.xs),
        Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            for (final e in template.equipmentOptions)
              FilterChip(
                key: Key('scope-equipment-$e'),
                label: Text(
                  scopeEquipmentText(l10n, e),
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                materialTapTargetSize: MaterialTapTargetSize.padded,
                padding: const EdgeInsetsDirectional.symmetric(
                  horizontal: AppSpacing.sm,
                  vertical: AppSpacing.sm,
                ),
                selected: scope.equipment.contains(e),
                onSelected: (on) => onChanged(
                  scope.copyWith(
                    equipment: [
                      for (final x in template.equipmentOptions)
                        if (x == e ? on : scope.equipment.contains(x)) x,
                    ],
                  ),
                ),
              ),
          ],
        ),
        if (_err(l10n, 'loading_equipment') != null)
          Text(
            _err(l10n, 'loading_equipment')!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        gap,
        ScopeStepper(
          key: const ValueKey('scope-waiting'),
          label: l10n.scWaiting,
          value: scope.waitingMinutes,
          min: 0,
          max: template.waitingMax,
          step: 15,
          suffix: l10n.scMinutesShort,
          error: _err(l10n, 'waiting_allowance_minutes'),
          onChanged: (v) => onChanged(scope.copyWith(waitingMinutes: v)),
        ),
        gap,
        ScopeStepper(
          key: const ValueKey('scope-stops'),
          label: l10n.scExtraStops,
          value: scope.extraStops,
          min: 0,
          max: template.extraStopsMax,
          error: _err(l10n, 'extra_stops_included'),
          onChanged: (v) => onChanged(scope.copyWith(extraStops: v)),
        ),
        gap,
        ScopeChoice(
          key: const ValueKey('scope-tolls'),
          label: l10n.scTolls,
          options: template.treatmentOptions,
          selected: scope.tolls,
          text: (v) => scopeTreatmentText(l10n, v),
          error: _err(l10n, 'tolls'),
          onChanged: (v) => onChanged(scope.copyWith(tolls: v)),
        ),
        gap,
        ScopeChoice(
          key: const ValueKey('scope-parking'),
          label: l10n.scParking,
          options: template.treatmentOptions,
          selected: scope.parking,
          text: (v) => scopeTreatmentText(l10n, v),
          error: _err(l10n, 'parking'),
          onChanged: (v) => onChanged(scope.copyWith(parking: v)),
        ),
        gap,
        TextFormField(
          key: const Key('scope-exclusions'),
          initialValue: scope.exclusions,
          maxLength: template.exclusionsMax,
          minLines: 2,
          maxLines: 4,
          decoration: InputDecoration(
            labelText: l10n.scExclusions,
            helperText: l10n.scExclusionsHelp,
            errorText: _err(l10n, 'exclusions'),
          ),
          onChanged: (v) => onChanged(scope.copyWith(exclusions: v)),
        ),
      ],
    );
  }
}
