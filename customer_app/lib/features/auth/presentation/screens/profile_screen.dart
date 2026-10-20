import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/providers.dart';
import '../providers/auth_providers.dart';
import '../widgets/auth_error_text.dart';

/// Profile completion right after the first sign-in: name (required), email
/// (optional). The chosen app language becomes the preferred language.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  bool _busy = false;
  bool _submitted = false;
  String? _serverError;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    super.dispose();
  }

  bool get _emailOk {
    final v = _email.text.trim();
    return v.isEmpty || RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(v);
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context);
    setState(() => _submitted = true);
    if (_name.text.trim().length < 2 || !_emailOk) return;
    setState(() {
      _busy = true;
      _serverError = null;
    });
    try {
      await ref
          .read(authActionsProvider)
          .updateProfile(
            ProfileUpdate(
              name: _name.text.trim(),
              email: _email.text.trim().isEmpty ? null : _email.text.trim(),
              preferredLocale: ref.read(localeProvider).languageCode,
            ),
          );
    } on Object catch (e) {
      if (mounted) setState(() => _serverError = authErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.profileTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            Text(l10n.profileSubtitle),
            const SizedBox(height: AppSpacing.lg),
            AppTextField(
              label: l10n.profileNameLabel,
              controller: _name,
              textInputAction: TextInputAction.next,
              errorText: _submitted && _name.text.trim().length < 2
                  ? l10n.profileNameInvalid
                  : null,
              onChanged: (_) => setState(() {}),
            ),
            const SizedBox(height: AppSpacing.md),
            AppTextField(
              label: l10n.profileEmailLabel,
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              textDirection: TextDirection.ltr,
              errorText: _submitted && !_emailOk
                  ? l10n.profileEmailInvalid
                  : null,
              onChanged: (_) => setState(() {}),
              onSubmitted: (_) => _save(),
            ),
            if (_serverError != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.md),
                child: Text(
                  _serverError!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              label: l10n.actionContinue,
              loading: _busy,
              onPressed: _save,
            ),
          ],
        ),
      ),
    );
  }
}
