import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl_phone_field/intl_phone_field.dart';

import 'tokens.dart';

/// Phone input with a country picker, Egypt (+20) preselected. Reports the
/// full E.164 number (e.g. `+201001234567`) and whether it is valid.
class PhoneField extends StatelessWidget {
  const PhoneField({
    super.key,
    required this.label,
    required this.onChanged,
    this.initialCountryCode = 'EG',
    this.languageCode = 'ar',
    this.errorText,
    this.invalidMessage,
    this.controller,
  });

  final String label;
  final String initialCountryCode;
  final String languageCode;
  final String? errorText;
  final String? invalidMessage;
  final TextEditingController? controller;

  /// (e164Number, isValid)
  final void Function(String e164, bool isValid) onChanged;

  @override
  Widget build(BuildContext context) => Directionality(
    // Phone numbers are always LTR, but the field keeps layout direction.
    textDirection: Directionality.of(context),
    child: IntlPhoneField(
      controller: controller,
      initialCountryCode: initialCountryCode,
      languageCode: languageCode,
      disableLengthCheck: false,
      keyboardType: TextInputType.phone,
      invalidNumberMessage: invalidMessage,
      decoration: InputDecoration(labelText: label, errorText: errorText),
      onChanged: (phone) {
        var valid = false;
        try {
          valid = phone.isValidNumber();
        } catch (_) {
          valid = false;
        }
        onChanged(phone.completeNumber, valid);
      },
    ),
  );
}

/// One-time-code input: [length] boxes backed by one hidden text field, so
/// paste, SMS autofill and the numeric keyboard all work. Always LTR digits.
class OtpField extends StatefulWidget {
  const OtpField({
    super.key,
    required this.onCompleted,
    this.length = 6,
    this.controller,
    this.errorText,
    this.enabled = true,
    this.onChanged,
  });

  final int length;
  final TextEditingController? controller;
  final ValueChanged<String> onCompleted;
  final ValueChanged<String>? onChanged;
  final String? errorText;
  final bool enabled;

  @override
  State<OtpField> createState() => _OtpFieldState();
}

class _OtpFieldState extends State<OtpField> {
  late final TextEditingController _controller =
      widget.controller ?? TextEditingController();
  final _focus = FocusNode();

  @override
  void initState() {
    super.initState();
    _controller.addListener(_onText);
  }

  @override
  void dispose() {
    _controller.removeListener(_onText);
    if (widget.controller == null) _controller.dispose();
    _focus.dispose();
    super.dispose();
  }

  String _lastReported = '';

  void _onText() {
    final text = _controller.text;
    setState(() {});
    if (text == _lastReported) return;
    _lastReported = text;
    widget.onChanged?.call(text);
    if (text.length == widget.length) widget.onCompleted(text);
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final size = context.density.minTouchTarget + 8;
    final text = _controller.text;
    final hasError = widget.errorText != null;
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Stack(
            alignment: Alignment.center,
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  for (var i = 0; i < widget.length; i++)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: Container(
                        width: size,
                        height: size,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(AppRadius.md),
                          border: Border.all(
                            color: hasError
                                ? scheme.error
                                : (i == text.length && _focus.hasFocus
                                      ? scheme.primary
                                      : scheme.outline),
                            width: hasError || i == text.length ? 2 : 1,
                          ),
                        ),
                        child: Text(
                          i < text.length ? text[i] : '',
                          style: Theme.of(context).textTheme.headlineSmall,
                        ),
                      ),
                    ),
                ],
              ),
              Positioned.fill(
                child: Opacity(
                  opacity: 0,
                  child: TextField(
                    controller: _controller,
                    focusNode: _focus,
                    enabled: widget.enabled,
                    autofocus: true,
                    keyboardType: TextInputType.number,
                    autofillHints: const [AutofillHints.oneTimeCode],
                    maxLength: widget.length,
                    enableInteractiveSelection: false,
                    showCursor: false,
                    inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                    decoration: const InputDecoration(counterText: ''),
                    onChanged: (_) {},
                  ),
                ),
              ),
            ],
          ),
          if (hasError)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.sm),
              child: Text(
                widget.errorText!,
                style: TextStyle(color: scheme.error),
              ),
            ),
        ],
      ),
    );
  }
}

/// Lets the user pick/capture images. Labels are supplied by the caller (l10n).
class AttachmentPicker extends StatelessWidget {
  const AttachmentPicker({
    super.key,
    required this.files,
    required this.onChanged,
    required this.addLabel,
    required this.cameraLabel,
    required this.galleryLabel,
    required this.removeLabel,
    this.maxFiles = 5,
    this._picker,
  });

  final List<XFile> files;
  final ValueChanged<List<XFile>> onChanged;
  final String addLabel;
  final String cameraLabel;
  final String galleryLabel;
  final String removeLabel;
  final int maxFiles;
  final ImagePicker? _picker;

  Future<void> _pick(BuildContext context) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: Text(cameraLabel),
              onTap: () => Navigator.pop(ctx, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text(galleryLabel),
              onTap: () => Navigator.pop(ctx, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return;
    final picked = await (_picker ?? ImagePicker()).pickImage(
      source: source,
      imageQuality: 85,
      maxWidth: 2048,
    );
    if (picked != null) onChanged([...files, picked]);
  }

  @override
  Widget build(BuildContext context) => Wrap(
    spacing: AppSpacing.sm,
    runSpacing: AppSpacing.sm,
    children: [
      for (final f in files)
        Stack(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              child: Image.file(
                File(f.path),
                width: 80,
                height: 80,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => const SizedBox.square(
                  dimension: 80,
                  child: Icon(Icons.broken_image_outlined),
                ),
              ),
            ),
            PositionedDirectional(
              top: 0,
              end: 0,
              child: Semantics(
                label: removeLabel,
                button: true,
                child: InkResponse(
                  onTap: () => onChanged(files.where((x) => x != f).toList()),
                  child: const CircleAvatar(
                    radius: 12,
                    child: Icon(Icons.close, size: 14),
                  ),
                ),
              ),
            ),
          ],
        ),
      if (files.length < maxFiles)
        OutlinedButton.icon(
          onPressed: () => _pick(context),
          icon: const Icon(Icons.add_a_photo_outlined),
          label: Text(addLabel),
        ),
    ],
  );
}
