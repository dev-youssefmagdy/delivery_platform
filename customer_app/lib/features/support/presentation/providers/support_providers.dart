import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/providers.dart';
import '../../../../core/realtime_providers.dart';

Duration? _noAutoRetry(int retryCount, Object error) => null;

final chatRepositoryProvider = Provider<ChatRepository>(
  (ref) => ApiChatRepository(
    ChatRemoteDataSource(ref.watch(apiClientProvider), prefix: '/bookings'),
  ),
);

final supportRepositoryProvider = Provider<SupportRepository>(
  (ref) => ApiSupportRepository(
    SupportRemoteDataSource(ref.watch(apiClientProvider), bookingPrefix: '/bookings'),
  ),
);

/// Maps a failure of the chat or support calls to ARB text. The stable error
/// code decides; the server message is the last fallback.
String supportErrorText(AppLocalizations l10n, Object error) {
  if (error is! ApiException) return l10n.errorGenericBody;
  if (error.isOffline) return l10n.errorOfflineBody;
  if (error.isSessionExpired) return l10n.errorSessionExpiredBody;
  return supportCodeText(l10n, error.code) ?? error.message ?? l10n.errorGenericBody;
}

/// ARB text for a stable error [code], or null when the app has none.
String? supportCodeText(AppLocalizations l10n, String code) {
  return switch (code) {
    'chat_closed' => l10n.chatErrClosed,
    'chat_forbidden' => l10n.chatErrForbidden,
    'chat_message_too_long' => l10n.chatErrTooLong,
    'ticket_limit_reached' => l10n.supportErrLimit,
    'ticket_rate_limited' => l10n.supportErrRate,
    'ticket_closed' || 'ticket_finished' => l10n.supportErrClosed,
    'appeal_already_open' => l10n.supportErrAppealOpen,
    'appeal_not_available' => l10n.supportErrAppealNone,
    'document_infected' => l10n.supportErrInfected,
    'document_type_not_allowed' || 'document_unreadable' || 'document_pdf_active_content' => l10n.supportErrFileType,
    'document_too_large' || 'document_image_too_large' => l10n.supportErrFileSize,
    _ => null,
  };
}

// ---------------------------------------------------------------------------
// Booking chat

const _chatPollOffline = Duration(seconds: 5);
const _chatPollLive = Duration(seconds: 30);

/// One booking's conversation: the latest page, refetched on every realtime hint
/// (`booking.message_received` / `booking.messages_read` on the customer's own
/// channel), on reconnect and by a polling fallback. Sending is optimistic: the
/// message shows immediately and can be retried with the same Idempotency-Key.
class ChatNotifier extends AsyncNotifier<ChatState> {
  ChatNotifier(this.bookingId);

  final String bookingId;
  bool _refreshing = false;
  DateTime _last = DateTime.now();

  ChatRepository get _repo => ref.read(chatRepositoryProvider);

  @override
  Future<ChatState> build() async {
    ref.watch(sessionStatusProvider);
    final hub = ref.watch(customerEventsProvider);
    final subs = <StreamSubscription<Object?>>[
      hub.events.listen((e) {
        if ((e.name == 'booking.message_received' || e.name == 'booking.messages_read') && e.data['booking_uuid'] == bookingId) {
          unawaited(refresh());
        }
      }),
      hub.reconnected.listen((_) => unawaited(refresh())),
    ];
    final timer = Timer.periodic(_chatPollOffline, (_) {
      final every = hub.isLive ? _chatPollLive : _chatPollOffline;
      if (DateTime.now().difference(_last) >= every) unawaited(refresh());
    });
    ref.onDispose(() {
      timer.cancel();
      for (final s in subs) {
        s.cancel();
      }
    });

    final snapshot = await _repo.messages(bookingId);
    _last = DateTime.now();
    final next = ChatState.fromSnapshot(snapshot);
    unawaited(_markRead(next));

    return next;
  }

  Future<void> reload() async {
    ref.invalidateSelf();
    await future;
  }

  /// Refetches the latest page; a failure keeps what is on screen.
  Future<void> refresh() async {
    final current = state.value;
    if (current == null || _refreshing) return;
    _refreshing = true;
    try {
      final fresh = await _repo.messages(bookingId);
      _last = DateTime.now();
      final merged = (state.value ?? current).withFreshPage(fresh);
      state = AsyncData(merged);
      unawaited(_markRead(merged));
    } on Object {
      // Keep the last good state; the next hint or poll retries.
    } finally {
      _refreshing = false;
    }
  }

  Future<void> loadOlder() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    final oldest = current.messages.where((m) => !m.isLocal).firstOrNull;
    if (oldest == null) return;
    state = AsyncData(current.copyWith(loadingMore: true));
    try {
      final older = await _repo.messages(bookingId, beforeId: oldest.id);
      state = AsyncData((state.value ?? current).withOlderPage(older));
    } on Object {
      state = AsyncData((state.value ?? current).copyWith(loadingMore: false));
    }
  }

  Future<void> send(String? text, String? imagePath) async {
    final current = state.value;
    if (current == null) return;
    final key = newClientKey();
    final local = ChatMessage.local(clientKey: key, body: text, localAttachmentPath: imagePath);
    state = AsyncData(current.withLocal(local));
    await _deliver(local);
  }

  Future<void> retry(ChatMessage message) async {
    final key = message.clientKey;
    final current = state.value;
    if (key == null || current == null) return;
    state = AsyncData(current.withSending(key));
    await _deliver(message);
  }

  void discard(ChatMessage message) {
    final key = message.clientKey;
    final current = state.value;
    if (key == null || current == null) return;
    state = AsyncData(current.without(key));
  }

  Future<void> _deliver(ChatMessage local) async {
    final key = local.clientKey!;
    try {
      final saved = await _repo.send(bookingId, clientKey: key, body: local.body, imagePath: local.localAttachmentPath);
      final current = state.value;
      if (current != null) state = AsyncData(current.withConfirmed(key, saved));
    } on Object catch (e) {
      final current = state.value;
      if (current == null) return;
      // A rule the server enforces (window closed, too long ...) will not pass on retry: show why and drop the draft.
      final permanent = e is ApiException && (e.code == 'chat_closed' || e.code == 'chat_forbidden' || e.code == 'chat_message_too_long' || e.code.startsWith('document_'));
      state = AsyncData(current.withFailed(key, error: permanent ? e.code : null));
      if (e is ApiException && (e.code == 'chat_closed' || e.code == 'chat_forbidden')) await refresh();
    }
  }

  Future<void> _markRead(ChatState chat) async {
    if (!chat.hasUnreadFromOther && chat.unread == 0) return;
    try {
      await _repo.markRead(bookingId);
    } on Object {
      // Best effort: the next refresh tries again.
    }
  }
}

final chatProvider = AsyncNotifierProvider.autoDispose
    .family<ChatNotifier, ChatState, String>(ChatNotifier.new, retry: _noAutoRetry);

// ---------------------------------------------------------------------------
// Tickets

class TicketsState {
  const TicketsState({
    required this.items,
    required this.page,
    required this.hasMore,
    required this.filter,
    this.loadingMore = false,
  });

  final List<SupportTicket> items;
  final int page;
  final bool hasMore;
  final String? filter;
  final bool loadingMore;

  TicketsState copyWith({List<SupportTicket>? items, int? page, bool? hasMore, bool? loadingMore}) => TicketsState(
    items: items ?? this.items,
    page: page ?? this.page,
    hasMore: hasMore ?? this.hasMore,
    filter: filter,
    loadingMore: loadingMore ?? this.loadingMore,
  );
}

class TicketFilterNotifier extends Notifier<String?> {
  @override
  String? build() => null;

  void select(String? value) => state = value;
}

final ticketFilterProvider = NotifierProvider<TicketFilterNotifier, String?>(TicketFilterNotifier.new);

class TicketsNotifier extends AsyncNotifier<TicketsState> {
  SupportRepository get _repo => ref.read(supportRepositoryProvider);

  @override
  Future<TicketsState> build() async {
    ref.watch(sessionStatusProvider);
    final filter = ref.watch(ticketFilterProvider);
    final page = await _repo.tickets(status: filter);

    return TicketsState(items: page.items, page: page.currentPage, hasMore: page.hasMore, filter: filter);
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(current.copyWith(loadingMore: true));
    try {
      final next = await _repo.tickets(page: current.page + 1, status: current.filter);
      state = AsyncData(current.copyWith(items: [...current.items, ...next.items], page: next.currentPage, hasMore: next.hasMore, loadingMore: false));
    } on Object {
      state = AsyncData(current.copyWith(loadingMore: false));
    }
  }
}

final ticketsProvider = AsyncNotifierProvider<TicketsNotifier, TicketsState>(TicketsNotifier.new, retry: _noAutoRetry);

/// One ticket with its public thread. Refreshes when the person comes back to it.
class TicketDetailNotifier extends AsyncNotifier<SupportTicket> {
  TicketDetailNotifier(this.ticketId);

  final String ticketId;

  @override
  Future<SupportTicket> build() {
    ref.watch(sessionStatusProvider);
    return ref.read(supportRepositoryProvider).ticket(ticketId);
  }

  Future<void> refresh() async {
    try {
      state = AsyncData(await ref.read(supportRepositoryProvider).ticket(ticketId));
    } on Object {
      // Keep what is shown.
    }
  }

  /// Sends a reply with optional evidence. Returns the saved ticket or throws [ApiException].
  Future<void> reply(String body, List<String> attachmentPaths, String key) async {
    final saved = await ref.read(supportRepositoryProvider).reply(ticketId, key: key, body: body, attachmentPaths: attachmentPaths);
    state = AsyncData(saved);
    ref.invalidate(ticketsProvider);
  }
}

final ticketDetailProvider = AsyncNotifierProvider.autoDispose
    .family<TicketDetailNotifier, SupportTicket, String>(TicketDetailNotifier.new, retry: _noAutoRetry);

final ticketCategoriesProvider = FutureProvider.autoDispose<List<TicketCategoryOption>>((ref) {
  ref.watch(sessionStatusProvider);
  return ref.read(supportRepositoryProvider).categories();
});

/// Opens tickets and appeals; refreshes the list afterwards.
class SupportActions {
  SupportActions(this._ref);

  final Ref _ref;

  Future<SupportTicket> open(NewTicketInput input, String key) async {
    final ticket = await _ref.read(supportRepositoryProvider).open(input, key: key);
    _ref.invalidate(ticketsProvider);
    return ticket;
  }

  Future<SupportTicket> appeal(String bookingId, String reason, String key) async {
    final ticket = await _ref.read(supportRepositoryProvider).appeal(bookingId, key: key, reason: reason);
    _ref.invalidate(ticketsProvider);
    return ticket;
  }
}

final supportActionsProvider = Provider<SupportActions>(SupportActions.new);
