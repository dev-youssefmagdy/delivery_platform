import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

/// A two-button confirmation with large buttons. Returns true on confirm.
Future<bool> showJobConfirm(
  BuildContext context, {
  required String title,
  required String body,
  required String confirmLabel,
  bool destructive = false,
}) async {
  final result = await showModalBottomSheet<bool>(
    context: context,
    showDragHandle: true,
    useSafeArea: true,
    builder: (ctx) {
      final l10n = AppLocalizations.of(ctx);
      return Padding(
        padding: const EdgeInsetsDirectional.fromSTEB(
          AppSpacing.md,
          0,
          AppSpacing.md,
          AppSpacing.md,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(title, style: Theme.of(ctx).textTheme.titleLarge),
            const SizedBox(height: AppSpacing.sm),
            Text(body),
            const SizedBox(height: AppSpacing.md),
            FilledButton(
              key: const Key('confirm-yes'),
              style: destructive
                  ? FilledButton.styleFrom(
                      backgroundColor: Theme.of(ctx).colorScheme.error,
                    )
                  : null,
              onPressed: () => Navigator.of(ctx).pop(true),
              child: Text(confirmLabel),
            ),
            const SizedBox(height: AppSpacing.sm),
            OutlinedButton(
              key: const Key('confirm-no'),
              onPressed: () => Navigator.of(ctx).pop(false),
              child: Text(l10n.actionBack),
            ),
          ],
        ),
      );
    },
  );
  return result ?? false;
}
