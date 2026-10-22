import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/notification_providers.dart';

/// Explains where push stands on this device and offers the one action that
/// helps: enabling it (after a rationale), or nothing when it is not available.
class PushStatusCard extends ConsumerWidget {
  const PushStatusCard({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final coordinator = ref.watch(pushCoordinatorProvider);
    return ValueListenableBuilder<PushStatus?>(
      valueListenable: coordinator.status,
      builder: (context, status, _) {
        final (icon, text) = switch (status) {
          PushStatus.ready => (Icons.notifications_active, l10n.pushStatusReady),
          PushStatus.permissionNeeded => (Icons.notifications_off_outlined, l10n.pushStatusPermissionNeeded),
          PushStatus.denied => (Icons.block, l10n.pushStatusDenied),
          PushStatus.failed => (Icons.error_outline, l10n.pushStatusFailed),
          PushStatus.notConfigured => (Icons.cloud_off_outlined, l10n.pushStatusNotConfigured),
          null => (Icons.hourglass_empty, l10n.loadingLabel),
        };
        return Card(
          margin: const EdgeInsetsDirectional.all(16),
          child: ListTile(
            key: const Key('push-status'),
            leading: Icon(icon),
            title: Text(text),
            trailing: status == PushStatus.permissionNeeded
                ? FilledButton(
                    key: const Key('push-enable'),
                    onPressed: () async {
                      final ok = await showPushRationale(context);
                      if (ok) await coordinator.enable();
                    },
                    child: Text(l10n.pushEnable),
                  )
                : null,
          ),
        );
      },
    );
  }
}

/// Why we ask, before the system dialog appears.
Future<bool> showPushRationale(BuildContext context) async {
  final l10n = AppLocalizations.of(context);
  final result = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      title: Text(l10n.pushRationaleTitle),
      content: Text(l10n.pushRationaleBody),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(false),
          child: Text(l10n.pushRationaleLater),
        ),
        FilledButton(
          onPressed: () => Navigator.of(context).pop(true),
          child: Text(l10n.pushEnable),
        ),
      ],
    ),
  );
  return result ?? false;
}
