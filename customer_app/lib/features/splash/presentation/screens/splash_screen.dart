import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/providers.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer(ref.read(splashDurationProvider), _continue);
  }

  Future<void> _continue() async {
    // Let the startup config (maintenance / update rules, market) arrive
    // first, but never block the app on it: errors fall back to the cache.
    try {
      await ref
          .read(appConfigProvider.future)
          .timeout(ref.read(configWaitProvider));
    } on Object {
      // Unavailable config is handled by the gate (fails open).
    }
    if (ref.read(sessionStatusProvider) == SessionStatus.authenticated) {
      // The signed-in user's status decides the first screen.
      try {
        await ref.read(meProvider.future).timeout(ref.read(configWaitProvider));
      } on Object {
        // A failed /me is shown by the account-status retry screen.
      }
    }
    if (!mounted) return;
    context.go(
      destinationAfterSplash(
        session: ref.read(sessionStatusProvider),
        languageChosen: ref.read(languageChosenProvider),
        onboardingDone: ref.read(onboardingDoneProvider),
      ),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    return Scaffold(
      backgroundColor: theme.colorScheme.primary,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.local_taxi_rounded,
              size: 72,
              color: theme.colorScheme.onPrimary,
            ),
            const SizedBox(height: 16),
            Text(
              l10n.appName,
              style: theme.textTheme.headlineMedium?.copyWith(
                color: theme.colorScheme.onPrimary,
              ),
            ),
            const SizedBox(height: 24),
            SizedBox.square(
              dimension: 24,
              child: CircularProgressIndicator(
                strokeWidth: 2.5,
                semanticsLabel: l10n.splashLoading,
                color: theme.colorScheme.onPrimary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
