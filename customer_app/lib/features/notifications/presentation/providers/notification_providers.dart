import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/providers.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../booking/presentation/providers/booking_providers.dart';
import '../../../support/presentation/providers/support_providers.dart';

Duration? _noAutoRetry(int retryCount, Object error) => null;

final notificationRepositoryProvider = Provider<NotificationRepository>(
  (ref) => ApiNotificationRepository(
    NotificationRemoteDataSource(ref.watch(apiClientProvider)),
  ),
);

/// The screen a notification opens in this app, or null when the app has no
/// such screen yet (the inbox is shown instead). Pure: unit tested.
String? customerRouteFor(NotificationLink link) {
  final id = link.id;
  return switch (link.type) {
    NotificationLinkType.booking when id != null => Routes.booking(id),
    NotificationLinkType.chat when id != null => Routes.bookingChat(id),
    NotificationLinkType.wallet => Routes.wallet,
    NotificationLinkType.support when id != null => Routes.supportTicket(id),
    NotificationLinkType.support => Routes.support,
    NotificationLinkType.safety when id != null => Routes.safetyIncident(id),
    NotificationLinkType.safety => Routes.safetyIncidents,
    NotificationLinkType.inbox => Routes.notifications,
    _ => null,
  };
}

class InboxState {
  const InboxState({
    required this.items,
    required this.unread,
    required this.page,
    required this.hasMore,
    this.loadingMore = false,
  });

  final List<AppNotification> items;
  final int unread;
  final int page;
  final bool hasMore;
  final bool loadingMore;

  InboxState copyWith({
    List<AppNotification>? items,
    int? unread,
    int? page,
    bool? hasMore,
    bool? loadingMore,
  }) => InboxState(
    items: items ?? this.items,
    unread: unread ?? this.unread,
    page: page ?? this.page,
    hasMore: hasMore ?? this.hasMore,
    loadingMore: loadingMore ?? this.loadingMore,
  );
}

/// The in-app inbox (newest first, paginated).
class InboxNotifier extends AsyncNotifier<InboxState> {
  NotificationRepository get _repo => ref.read(notificationRepositoryProvider);

  @override
  Future<InboxState> build() async {
    ref.watch(sessionStatusProvider);
    final page = await _repo.list();
    return InboxState(
      items: page.items,
      unread: page.unreadCount,
      page: page.currentPage,
      hasMore: page.hasMore,
    );
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(current.copyWith(loadingMore: true));
    try {
      final next = await _repo.list(page: current.page + 1);
      state = AsyncData(
        current.copyWith(
          items: [...current.items, ...next.items],
          unread: next.unreadCount,
          page: next.currentPage,
          hasMore: next.hasMore,
          loadingMore: false,
        ),
      );
    } on Object {
      state = AsyncData(current.copyWith(loadingMore: false));
    }
  }

  Future<void> markRead(AppNotification item) async {
    final current = state.value;
    if (current == null || item.read) return;
    state = AsyncData(
      current.copyWith(
        unread: current.unread > 0 ? current.unread - 1 : 0,
        items: [
          for (final i in current.items) i.id == item.id ? i.asRead() : i,
        ],
      ),
    );
    try {
      await _repo.markRead(item.id);
    } on Object {
      ref.invalidateSelf();
    }
  }

  Future<void> markAllRead() async {
    final current = state.value;
    if (current == null || current.unread == 0) return;
    await _repo.markAllRead();
    state = AsyncData(
      current.copyWith(
        unread: 0,
        items: [for (final i in current.items) i.asRead()],
      ),
    );
  }
}

final inboxProvider = AsyncNotifierProvider<InboxNotifier, InboxState>(
  InboxNotifier.new,
  retry: _noAutoRetry,
);

/// Unread count for badges; 0 until the inbox loads.
final unreadCountProvider = Provider<int>(
  (ref) => ref.watch(inboxProvider).value?.unread ?? 0,
);

/// Notification settings per category and channel.
class PreferencesNotifier extends AsyncNotifier<NotificationPreferences> {
  @override
  Future<NotificationPreferences> build() {
    ref.watch(sessionStatusProvider);
    return ref.read(notificationRepositoryProvider).preferences();
  }

  /// Saves one change; the server's answer (which also reports locked
  /// channels) replaces the screen state. Throws [ApiException] on failure.
  Future<void> set(
    NotificationCategory category,
    NotificationChannel channel,
    bool enabled,
  ) async {
    final saved = await ref
        .read(notificationRepositoryProvider)
        .updatePreferences([PreferenceChange(category, channel, enabled)]);
    state = AsyncData(saved);
  }
}

final preferencesProvider =
    AsyncNotifierProvider<PreferencesNotifier, NotificationPreferences>(
      PreferencesNotifier.new,
      retry: _noAutoRetry,
    );

/// Push for the signed-in session. Started by the home shell, stopped on sign-out.
final pushCoordinatorProvider = Provider<PushCoordinator>((ref) {
  final router = ref.read(routerProvider);
  final repo = ref.read(notificationRepositoryProvider);
  final gateway = FirebasePushGateway();
  final coordinator = PushCoordinator(
    gateway: gateway,
    registrar: PushRegistrar(
      gateway: gateway,
      repository: repo,
      deviceId: () => DeviceIdStore(ref.read(sharedPreferencesProvider)).id,
      platform: kIsWeb ? 'web' : (Platform.isIOS ? 'ios' : 'android'),
      appVersion: () => ref.read(appVersionProvider.future),
      languageCode: () => ref.read(localeProvider).languageCode,
    ),
    links: DeepLinkHandler(
      routeFor: customerRouteFor,
      validate: (link) async {
        if ((link.type == NotificationLinkType.booking ||
                link.type == NotificationLinkType.chat) &&
            link.id != null) {
          await ref.read(bookingRepositoryProvider).get(link.id!);
        }
        if (link.type == NotificationLinkType.support && link.id != null) {
          await ref.read(supportRepositoryProvider).ticket(link.id!);
        }
        return true;
      },
      open: (route) => router.push(route),
      openInbox: () => router.push(Routes.notifications),
      onUnavailable: () {
        final context = router.routerDelegate.navigatorKey.currentContext;
        if (context == null) return;
        ScaffoldMessenger.maybeOf(context)?.showSnackBar(
          SnackBar(
            content: Text(AppLocalizations.of(context).notificationUnavailable),
          ),
        );
      },
      markRead: (id) async {
        await repo.markRead(id);
        ref.invalidate(inboxProvider);
      },
    ),
    onForeground: () => ref.invalidate(inboxProvider),
  );
  ref.onDispose(coordinator.dispose);
  return coordinator;
});
