import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/availability_providers.dart';

/// The state of the live connection: green when events flow, amber while
/// (re)connecting, red when there is none. Never colour only: it has a text.
class RealtimeIndicator extends ConsumerWidget {
  const RealtimeIndicator({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final status = ref.watch(realtimeControllerProvider);
    final (color, label) = switch (status.connection) {
      RealtimeConnectionState.connected => (
        AppColors.success,
        l10n.avRealtimeConnected,
      ),
      RealtimeConnectionState.connecting => (
        AppColors.warning,
        l10n.avRealtimeConnecting,
      ),
      RealtimeConnectionState.disconnected => (
        AppColors.danger,
        l10n.avRealtimeDisconnected,
      ),
    };
    return Semantics(
      label: label,
      container: true,
      child: Row(
        key: Key('realtime-indicator-${status.connection.name}'),
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 10,
            height: 10,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: AppSpacing.xs),
          Flexible(
            child: Text(label, style: Theme.of(context).textTheme.labelMedium),
          ),
        ],
      ),
    );
  }
}
