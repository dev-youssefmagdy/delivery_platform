import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../providers/notification_providers.dart';
import '../widgets/push_status_card.dart';

/// Notification settings: per category and channel. Essential categories
/// (trips, account, payments, safety) keep push on and say why.
class NotificationSettingsScreen extends ConsumerWidget {
  const NotificationSettingsScreen({super.key});

  String _channelLabel(AppLocalizations l10n, NotificationChannel channel) =>
      switch (channel) {
        NotificationChannel.push => l10n.notificationChannelPush,
        NotificationChannel.sms => l10n.notificationChannelSms,
        NotificationChannel.email => l10n.notificationChannelEmail,
        _ => l10n.notificationChannelInApp,
      };

  Future<void> _toggle(
    BuildContext context,
    WidgetRef ref,
    CategoryPreference category,
    ChannelPreference channel,
    bool value,
  ) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ref
          .read(preferencesProvider.notifier)
          .set(category.category, channel.channel, value);
    } on ApiException catch (e) {
      messenger.showSnackBar(
        SnackBar(content: Text(e.message ?? l10n.errorGenericBody)),
      );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final prefs = ref.watch(preferencesProvider);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.notificationSettingsTitle)),
      body: AsyncStateView<NotificationPreferences>(
        value: prefs,
        onRetry: () => ref.invalidate(preferencesProvider),
        onSignIn: () => context.go(Routes.auth),
        isEmpty: (p) => p.categories.isEmpty,
        builder: (context, data) => ListView(
          children: [
            const PushStatusCard(),
            Padding(
              padding: const EdgeInsetsDirectional.fromSTEB(16, 8, 16, 8),
              child: Text(l10n.notificationEssentialExplain),
            ),
            for (final category in data.categories)
              _CategoryTile(
                category: category,
                channelLabel: (c) => _channelLabel(l10n, c),
                onChanged: (channel, value) =>
                    _toggle(context, ref, category, channel, value),
              ),
          ],
        ),
      ),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  const _CategoryTile({
    required this.category,
    required this.channelLabel,
    required this.onChanged,
  });

  final CategoryPreference category;
  final String Function(NotificationChannel) channelLabel;
  final void Function(ChannelPreference channel, bool value) onChanged;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return ExpansionTile(
      key: Key('pref-${category.category.value}'),
      title: Text(category.label),
      subtitle: Text(
        category.essential
            ? '${category.description}\n${l10n.notificationEssentialBadge}'
            : category.optInRequired
            ? '${category.description}\n${l10n.notificationOptInBadge}'
            : category.description,
      ),
      children: [
        for (final channel in category.channels)
          SwitchListTile(
            key: Key('pref-${category.category.value}-${channel.channel.value}'),
            title: Text(channelLabel(channel.channel)),
            subtitle: channel.locked ? Text(l10n.notificationLockedOn) : null,
            value: channel.enabled,
            onChanged: channel.locked ? null : (v) => onChanged(channel, v),
          ),
      ],
    );
  }
}
