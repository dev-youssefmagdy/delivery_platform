import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/providers.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';

/// Language picker. Arabic is preselected; a choice applies immediately (the
/// whole UI flips RTL/LTR) and is persisted.
class LanguageScreen extends ConsumerWidget {
  const LanguageScreen({super.key, this.fromSettings = false});

  /// Opened from the home screen rather than first-run onboarding.
  final bool fromSettings;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final current = ref.watch(localeProvider).languageCode;
    final offered =
        ref.watch(appConfigProvider).value?.languageCodes ??
        supportedLanguageCodes;
    final options = [
      for (final (code, name) in [
        ('ar', l10n.languageArabicName),
        ('en', l10n.languageEnglishName),
      ])
        if (offered.contains(code) || code == current) (code, name),
    ];
    return Scaffold(
      appBar: AppBar(title: Text(l10n.languageTitle)),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                l10n.languageSubtitle,
                style: Theme.of(context).textTheme.bodyLarge,
              ),
              const SizedBox(height: AppSpacing.lg),
              RadioGroup<String>(
                groupValue: current,
                onChanged: (v) {
                  if (v != null) {
                    ref.read(localeProvider.notifier).setLanguage(v);
                  }
                },
                child: Column(
                  children: [
                    for (final (code, name) in options)
                      Card(
                        child: RadioListTile<String>(
                          key: Key('language-$code'),
                          value: code,
                          title: Text(name),
                        ),
                      ),
                  ],
                ),
              ),
              const Spacer(),
              AppButton(
                label: l10n.actionContinue,
                onPressed: () async {
                  // Persist the (possibly default) choice so first run is
                  // marked as "language chosen".
                  await ref.read(localeProvider.notifier).setLanguage(current);
                  if (!context.mounted) return;
                  if (fromSettings) {
                    context.go(Routes.providerOnboarding);
                  } else {
                    context.go(
                      ref.read(onboardingDoneProvider)
                          ? Routes.auth
                          : Routes.onboarding,
                    );
                  }
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
