import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/app_config_providers.dart';

/// Replaces the app with the maintenance or forced-update screen when the
/// server's config says so. Lives in `MaterialApp.router`'s builder, so it
/// covers every route.
class AppGate extends ConsumerWidget {
  const AppGate({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final status = ref.watch(appGateProvider);
    if (status == AppGateStatus.open) return child;

    final config = ref.watch(appConfigProvider).value;
    final l10n = AppLocalizations.of(context);
    void checkAgain() => ref.invalidate(appConfigProvider);

    return switch (status) {
      AppGateStatus.maintenance => BlockingNotice(
        key: const Key('maintenance-screen'),
        icon: Icons.construction_rounded,
        title: l10n.maintenanceTitle,
        message: l10n.maintenanceBody,
        details: [
          for (final contact in config?.support.lines ?? const <String>[])
            '${l10n.supportContactLabel}: $contact',
        ],
        actionLabel: l10n.actionCheckAgain,
        onAction: checkAgain,
      ),
      _ => BlockingNotice(
        key: const Key('force-update-screen'),
        icon: Icons.system_update_rounded,
        title: l10n.forceUpdateTitle,
        message: l10n.forceUpdateBody,
        details: [
          if (config?.minVersion != null)
            l10n.forceUpdateRequiredVersion(config!.minVersion!),
        ],
        actionLabel: l10n.actionCheckAgain,
        onAction: checkAgain,
      ),
    };
  }
}
