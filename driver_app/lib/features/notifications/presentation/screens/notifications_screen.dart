import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../providers/notification_providers.dart';

/// The in-app inbox: every notification, newest first; tap opens its target.
class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  Future<void> _open(
    BuildContext context,
    WidgetRef ref,
    AppNotification item,
  ) async {
    final l10n = AppLocalizations.of(context);
    final router = GoRouter.of(context);
    final messenger = ScaffoldMessenger.of(context);
    await ref.read(inboxProvider.notifier).markRead(item);
    final link = item.link;
    final route = link == null ? null : driverRouteFor(link);
    if (route == null || route == Routes.notifications) return;
    try {
      if (link!.id != null &&
          (link.type == NotificationLinkType.booking ||
              link.type == NotificationLinkType.chat ||
              link.type == NotificationLinkType.support)) {
        await ref.read(pushCoordinatorProvider).links.validate(link);
      }
      router.push(route);
    } on ApiException catch (e) {
      if (e.statusCode == 404 || e.statusCode == 403) {
        messenger.showSnackBar(
          SnackBar(content: Text(l10n.notificationUnavailable)),
        );
      } else {
        router.push(route);
      }
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final inbox = ref.watch(inboxProvider);
    final unread = ref.watch(unreadCountProvider);
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.notificationsTitle),
        actions: [
          if (unread > 0)
            TextButton(
              key: const Key('notifications-read-all'),
              onPressed: () => ref.read(inboxProvider.notifier).markAllRead(),
              child: Text(l10n.notificationsMarkAllRead),
            ),
          IconButton(
            key: const Key('notifications-settings'),
            tooltip: l10n.notificationSettingsTitle,
            icon: const Icon(Icons.tune),
            onPressed: () => context.push(Routes.notificationSettings),
          ),
        ],
      ),
      body: AsyncStateView<InboxState>(
        value: inbox,
        onRetry: () => ref.invalidate(inboxProvider),
        onSignIn: () => context.go(Routes.auth),
        isEmpty: (state) => state.items.isEmpty,
        builder: (context, state) => RefreshIndicator(
          onRefresh: () async => ref.refresh(inboxProvider.future),
          child: NotificationListener<ScrollNotification>(
            onNotification: (n) {
              if (n.metrics.pixels >= n.metrics.maxScrollExtent - 200) {
                ref.read(inboxProvider.notifier).loadMore();
              }
              return false;
            },
            child: ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              itemCount: state.items.length + (state.loadingMore ? 1 : 0),
              separatorBuilder: (_, _) => const Divider(height: 1),
              itemBuilder: (context, index) {
                if (index >= state.items.length) {
                  return const Padding(
                    padding: EdgeInsetsDirectional.all(16),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }
                final item = state.items[index];
                return ListTile(
                  key: Key('notification-${item.id}'),
                  minTileHeight: context.density.minTouchTarget,
                  leading: Icon(
                    item.read
                        ? Icons.notifications_none
                        : Icons.notifications_active,
                    color: item.read
                        ? null
                        : Theme.of(context).colorScheme.primary,
                  ),
                  title: Text(
                    item.title,
                    style: TextStyle(
                      fontWeight: item.read
                          ? FontWeight.normal
                          : FontWeight.w700,
                    ),
                  ),
                  subtitle: Text(
                    '${item.body}\n${DateFormat.yMMMd(Localizations.localeOf(context).toString()).add_Hm().format(item.createdAt.toLocal())}',
                  ),
                  isThreeLine: true,
                  onTap: () => _open(context, ref, item),
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}
