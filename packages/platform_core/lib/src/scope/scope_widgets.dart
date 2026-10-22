import 'package:flutter/material.dart';

import '../ui/tokens.dart';
import 'scope_models.dart';

/// Texts of the scope views; each app fills them from its ARB files.
class ScopeStrings {
  const ScopeStrings({
    required this.included,
    required this.excluded,
    required this.atCost,
    required this.title,
  });

  /// Spoken / tooltip text of the status icons.
  final String included;
  final String excluded;
  final String atCost;
  final String title;

  String of(ScopeLineStatus status) => switch (status) {
    ScopeLineStatus.included => included,
    ScopeLineStatus.excluded => excluded,
    ScopeLineStatus.atCost => atCost,
    ScopeLineStatus.neutral => '',
  };
}

/// The server's scope summary: one row per line with an icon saying whether
/// the item is included, excluded or paid at cost. Text is exactly what the
/// server sent (`label`, `value`); only the icon is derived from [scope].
/// Uses directional padding only, so it mirrors in RTL.
class ScopeSummaryList extends StatelessWidget {
  const ScopeSummaryList({
    super.key,
    required this.lines,
    required this.strings,
    this.scope,
    this.showTitle = true,
    this.dense = false,
  });

  final List<ScopeLine> lines;
  final Map<String, Object?>? scope;
  final ScopeStrings strings;
  final bool showTitle;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    if (lines.isEmpty) return const SizedBox.shrink();
    final theme = Theme.of(context);
    return Column(
      key: const Key('scope-summary'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (showTitle)
          Padding(
            padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.xs),
            child: Text(strings.title, style: theme.textTheme.labelLarge),
          ),
        for (final line in lines)
          _ScopeRow(
            key: Key('scope-line-${line.key}'),
            line: line,
            status: scopeLineStatus(line.key, scope),
            strings: strings,
            dense: dense,
          ),
      ],
    );
  }
}

class _ScopeRow extends StatelessWidget {
  const _ScopeRow({
    super.key,
    required this.line,
    required this.status,
    required this.strings,
    required this.dense,
  });

  final ScopeLine line;
  final ScopeLineStatus status;
  final ScopeStrings strings;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final (icon, color) = switch (status) {
      ScopeLineStatus.included => (Icons.check_circle, AppColors.success),
      ScopeLineStatus.excluded => (Icons.cancel_outlined, scheme.error),
      ScopeLineStatus.atCost => (Icons.receipt_long_outlined, AppColors.accent),
      ScopeLineStatus.neutral => (Icons.circle_outlined, scheme.outline),
    };
    return Semantics(
      label: '${line.label}: ${line.value}. ${strings.of(status)}',
      excludeSemantics: true,
      child: Padding(
        padding: EdgeInsetsDirectional.symmetric(vertical: dense ? 2 : 4),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, size: dense ? 18 : 20, color: color),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              flex: 4,
              child: Text(line.label, style: theme.textTheme.bodyMedium),
            ),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              flex: 5,
              child: Text(
                line.value,
                textAlign: TextAlign.end,
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
