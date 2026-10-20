import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import 'document_tile.dart';
import 'document_upload_screen.dart';

/// Step 4: the driver's own documents (vehicle documents are on each vehicle).
class DocumentsStep extends StatelessWidget {
  const DocumentsStep({
    super.key,
    required this.application,
    required this.onNext,
  });

  final ProviderApplication application;
  final VoidCallback onNext;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final documents = application.providerDocuments;
    final locked =
        application.started &&
        !application.editable &&
        application.status != ProviderStatus.approved;

    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Text(
          l10n.obDocumentsTitle,
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(l10n.obDocumentsIntro),
        const SizedBox(height: AppSpacing.md),
        if (documents.isEmpty)
          Text(l10n.obDocumentsEmpty, key: const Key('documents-empty')),
        for (final item in documents)
          DocumentTile(
            key: Key('document-${item.type.code}'),
            item: item,
            languageCode: language,
            onUpload: locked
                ? null
                : () => Navigator.of(context).push(
                    MaterialPageRoute<bool>(
                      builder: (_) => DocumentUploadScreen(type: item.type),
                    ),
                  ),
          ),
        const SizedBox(height: AppSpacing.lg),
        AppButton(label: l10n.actionNext, onPressed: onNext),
      ],
    );
  }
}
