import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/providers.dart';

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

  void _continue() {
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
