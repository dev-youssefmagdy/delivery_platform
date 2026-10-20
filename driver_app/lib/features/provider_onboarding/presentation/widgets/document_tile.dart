import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import 'onboarding_labels.dart';

/// One required document of the checklist: its name, state, expiry, the
/// reviewer's reason when rejected, and the upload action.
class DocumentTile extends StatelessWidget {
  const DocumentTile({
    super.key,
    required this.item,
    required this.languageCode,
    this.onUpload,
  });

  final RequiredDocument item;
  final String languageCode;

  /// Null hides the action (nothing to do, or the application is locked).
  final VoidCallback? onUpload;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final state = item.state;
    final expires = item.expiresAt ?? item.document?.expiresAt;
    final reason = state == DocumentStatus.rejected
        ? item.document?.rejectionReason
        : null;
    final actionLabel = switch (state) {
      DocumentStatus.expired => l10n.obDocRenew,
      DocumentStatus.rejected => l10n.obDocReplace,
      DocumentStatus.pending => l10n.obDocReplace,
      _ => l10n.obDocUpload,
    };

    return Card(
      margin: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    item.type.name.resolve(languageCode),
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                StatusChip(
                  label: documentStateLabel(l10n, state),
                  tone: documentStateTone(state),
                ),
              ],
            ),
            if (!item.mandatory)
              Text(
                l10n.obDocOptional,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            if (expires != null)
              Text(
                l10n.obDocExpires(formatDay(context, expires)),
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            if (reason != null && reason.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(
                l10n.obDocReason(reason),
                key: const Key('document-reason'),
                style: Theme.of(context).textTheme.bodyMedium
                    ?.copyWith(color: Theme.of(context).colorScheme.error),
              ),
            ],
            if (onUpload != null && state != DocumentStatus.approved) ...[
              const SizedBox(height: AppSpacing.sm),
              Align(
                alignment: AlignmentDirectional.centerStart,
                child: AppButton(
                  label: actionLabel,
                  icon: Icons.upload_file_outlined,
                  expand: false,
                  secondary: state == DocumentStatus.pending,
                  onPressed: onUpload,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
