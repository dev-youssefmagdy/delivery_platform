import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/providers.dart';
import '../providers/auth_providers.dart';
import '../widgets/auth_error_text.dart';

/// Phone entry (Egypt preselected). Requests an OTP and moves to the code
/// screen; the server answers identically for every number.
class AuthScreen extends ConsumerStatefulWidget {
  const AuthScreen({super.key});

  @override
  ConsumerState<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends ConsumerState<AuthScreen> {
  String _phone = '';
  bool _valid = false;
  bool _showInvalid = false;
  bool _busy = false;
  String? _error;

  Future<void> _submit() async {
    final l10n = AppLocalizations.of(context);
    if (!_valid) {
      setState(() => _showInvalid = true);
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(authActionsProvider).requestOtp(_phone);
      if (mounted) context.go(Routes.otp);
    } on Object catch (e) {
      if (mounted) setState(() => _error = authErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final language = ref.watch(localeProvider).languageCode;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.authTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            Text(
              l10n.authPhoneTitle,
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(l10n.authPhoneSubtitle),
            const SizedBox(height: AppSpacing.lg),
            PhoneField(
              label: l10n.authPhoneLabel,
              languageCode: language,
              invalidMessage: l10n.authPhoneInvalid,
              errorText: _showInvalid && !_valid ? l10n.authPhoneInvalid : null,
              onChanged: (e164, valid) => setState(() {
                _phone = e164;
                _valid = valid;
                _error = null;
              }),
            ),
            if (_error != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(
                  bottom: AppSpacing.md,
                ),
                child: Text(
                  _error!,
                  key: const Key('auth-error'),
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            const SizedBox(height: AppSpacing.md),
            AppButton(
              label: l10n.authSendCode,
              loading: _busy,
              onPressed: _submit,
            ),
          ],
        ),
      ),
    );
  }
}
