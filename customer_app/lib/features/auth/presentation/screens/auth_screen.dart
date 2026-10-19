import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

/// Entry point of the auth flow. Phone OTP sign-in ships with the identity API
/// in P1.3; until then this screen says so honestly instead of faking a form.
class AuthScreen extends StatelessWidget {
  const AuthScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.authTitle)),
      body: EmptyState(
        icon: Icons.lock_clock_outlined,
        title: l10n.authUnavailableTitle,
        message: l10n.authUnavailableBody,
      ),
    );
  }
}
