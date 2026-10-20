import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/provider_onboarding_providers.dart';
import 'date_field.dart';
import 'onboarding_labels.dart';

/// Uploads one document: a photo, the document number and the issue / expiry
/// dates the document type asks for. A failed upload keeps everything the
/// driver entered and offers a retry with the same idempotency key, so the
/// server stores the document once.
class DocumentUploadScreen extends ConsumerStatefulWidget {
  const DocumentUploadScreen({super.key, required this.type, this.vehicleId});

  final DocumentTypeInfo type;
  final String? vehicleId;

  @override
  ConsumerState<DocumentUploadScreen> createState() =>
      _DocumentUploadScreenState();
}

class _DocumentUploadScreenState extends ConsumerState<DocumentUploadScreen> {
  final _number = TextEditingController();
  // One key per user intent: kept across retries of the same upload, renewed
  // whenever the driver changes what is sent (a different payload needs a new key).
  String _intentKey = newIdempotencyKey();
  String? _path;
  DateTime? _issuedAt;
  DateTime? _expiresAt;
  bool _busy = false;
  bool _submitted = false;
  String? _error;

  @override
  void dispose() {
    _number.dispose();
    super.dispose();
  }

  Future<void> _choosePhoto() async {
    final l10n = AppLocalizations.of(context);
    final handler = ref.read(photoPickerFactoryProvider)(
      cameraLabel: l10n.photoCamera,
      galleryLabel: l10n.photoGallery,
    );
    final path = await handler.pick(context);
    if (path != null && mounted) {
      setState(() {
        _path = path;
        _renewIntent();
      });
    }
  }

  void _renewIntent() => _intentKey = newIdempotencyKey();

  String? _numberError(AppLocalizations l10n) =>
      _submitted && widget.type.requiresNumber && _number.text.trim().isEmpty
      ? l10n.validationRequired
      : null;

  String? _expiryError(AppLocalizations l10n) =>
      _submitted && widget.type.requiresExpiry && _expiresAt == null
      ? l10n.validationRequired
      : null;

  Future<void> _send() async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _submitted = true;
      _error = null;
    });
    if (_path == null) {
      setState(() => _error = l10n.obDocPhotoRequired);
      return;
    }
    if (_numberError(l10n) != null || _expiryError(l10n) != null) return;

    setState(() => _busy = true);
    try {
      await ref
          .read(onboardingActionsProvider)
          .uploadDocument(
            documentType: widget.type.code,
            filePath: _path!,
            idempotencyKey: _intentKey,
            number: _number.text,
            issuedAt: _issuedAt,
            expiresAt: _expiresAt,
            vehicleId: widget.vehicleId,
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(l10n.obDocSent)));
      Navigator.of(context).pop(true);
    } on Object catch (e) {
      if (mounted) setState(() => _error = onboardingErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final type = widget.type;
    final today = DateUtils.dateOnly(DateTime.now());

    return Scaffold(
      appBar: AppBar(title: Text(type.name.resolve(language))),
      body: ListView(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        children: [
          if (type.description.resolveOrNull(language) != null)
            Padding(
              padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.md),
              child: Text(type.description.resolve(language)),
            ),
          Text(
            l10n.obDocHint(
              type.allowedMimes.map(_mimeLabel).join(', '),
              type.maxSizeKb,
            ),
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: AppSpacing.md),
          Text(l10n.obDocPhoto, style: Theme.of(context).textTheme.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          if (_path != null)
            ClipRRect(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              child: Image.file(
                File(_path!),
                height: 180,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => const SizedBox(
                  height: 180,
                  child: Center(child: Icon(Icons.insert_drive_file_outlined)),
                ),
              ),
            ),
          const SizedBox(height: AppSpacing.sm),
          AppButton(
            key: const Key('document-choose-photo'),
            label: _path == null
                ? l10n.obDocChoosePhoto
                : l10n.obDocChangePhoto,
            icon: Icons.add_a_photo_outlined,
            secondary: true,
            onPressed: _busy ? null : _choosePhoto,
          ),
          const SizedBox(height: AppSpacing.md),
          if (type.requiresNumber) ...[
            AppTextField(
              label: l10n.obDocNumber,
              controller: _number,
              textDirection: TextDirection.ltr,
              errorText: _numberError(l10n),
              onChanged: (_) => setState(_renewIntent),
            ),
            const SizedBox(height: AppSpacing.md),
          ],
          DateField(
            fieldKey: const Key('document-issued-at'),
            label: l10n.obDocIssuedAt,
            value: _issuedAt,
            firstDate: DateTime(today.year - 60),
            lastDate: today,
            onPicked: (d) => setState(() {
              _issuedAt = d;
              _renewIntent();
            }),
            onClear: () => setState(() {
              _issuedAt = null;
              _renewIntent();
            }),
          ),
          if (type.requiresExpiry) ...[
            const SizedBox(height: AppSpacing.md),
            DateField(
              fieldKey: const Key('document-expires-at'),
              label: l10n.obDocExpiresAt,
              value: _expiresAt,
              firstDate: today,
              lastDate: DateTime(today.year + 30),
              initialDate: today.add(const Duration(days: 365)),
              onPicked: (d) => setState(() {
                _expiresAt = d;
                _renewIntent();
              }),
              errorText: _expiryError(l10n),
            ),
          ],
          if (_error != null) ...[
            const SizedBox(height: AppSpacing.md),
            Text(
              _error!,
              key: const Key('document-upload-error'),
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          ],
          const SizedBox(height: AppSpacing.lg),
          AppButton(
            key: const Key('document-send'),
            label: _error != null ? l10n.actionRetry : l10n.obDocSend,
            loading: _busy,
            onPressed: _send,
          ),
        ],
      ),
    );
  }

  static String _mimeLabel(String mime) => switch (mime) {
    'image/jpeg' => 'JPG',
    'image/png' => 'PNG',
    'image/webp' => 'WEBP',
    'application/pdf' => 'PDF',
    _ => mime,
  };
}
