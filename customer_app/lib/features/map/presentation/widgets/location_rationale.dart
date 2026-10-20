import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

/// Explains why location is needed BEFORE the OS prompt (Plan §6.1). Returns
/// true when the user agrees to continue.
Future<bool> showLocationRationale(BuildContext context) async {
  final l10n = AppLocalizations.of(context);
  final agreed = await showModalBottomSheet<bool>(
    context: context,
    showDragHandle: true,
    builder: (context) => SafeArea(
      child: Padding(
        padding: const EdgeInsetsDirectional.fromSTEB(
          AppSpacing.lg,
          0,
          AppSpacing.lg,
          AppSpacing.lg,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Icon(Icons.my_location, size: 40),
            const SizedBox(height: AppSpacing.md),
            Text(
              l10n.mapRationaleTitle,
              style: Theme.of(context).textTheme.titleLarge,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(l10n.mapRationaleBody, textAlign: TextAlign.center),
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              key: const Key('rationale-allow'),
              label: l10n.mapRationaleAllow,
              onPressed: () => Navigator.of(context).pop(true),
            ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              key: const Key('rationale-not-now'),
              label: l10n.mapRationaleNotNow,
              secondary: true,
              onPressed: () => Navigator.of(context).pop(false),
            ),
          ],
        ),
      ),
    ),
  );
  return agreed ?? false;
}
