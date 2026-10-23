import 'package:uuid/uuid.dart';

import 'support_models.dart';

/// A fresh Idempotency-Key for one user intent (send a message, open a ticket).
String newClientKey() => const Uuid().v4();

/// Everything the chat screen shows. Pure data with the merge rules the
/// notifiers of both apps share (events are hints; the server snapshot wins).
class ChatState {
  const ChatState({
    required this.messages,
    required this.canSend,
    required this.maxChars,
    this.closesAt,
    this.hasMore = false,
    this.loadingMore = false,
    this.unread = 0,
    this.sendError,
  });

  /// Oldest first: server messages followed by local (sending / failed) ones.
  final List<ChatMessage> messages;
  final bool canSend;
  final int maxChars;
  final DateTime? closesAt;
  final bool hasMore;
  final bool loadingMore;
  final int unread;
  final String? sendError;

  /// The newest message the server confirmed, or null.
  ChatMessage? get lastServerMessage {
    for (var i = messages.length - 1; i >= 0; i--) {
      if (!messages[i].isLocal) return messages[i];
    }
    return null;
  }

  /// The other side wrote something that has not been marked read yet.
  bool get hasUnreadFromOther =>
      messages.any((m) => !m.mine && m.readAt == null && !m.isLocal);

  ChatState copyWith({
    List<ChatMessage>? messages,
    bool? canSend,
    DateTime? closesAt,
    bool? hasMore,
    bool? loadingMore,
    int? unread,
    String? sendError,
    bool clearSendError = false,
  }) => ChatState(
    messages: messages ?? this.messages,
    canSend: canSend ?? this.canSend,
    maxChars: maxChars,
    closesAt: closesAt ?? this.closesAt,
    hasMore: hasMore ?? this.hasMore,
    loadingMore: loadingMore ?? this.loadingMore,
    unread: unread ?? this.unread,
    sendError: clearSendError ? null : (sendError ?? this.sendError),
  );

  factory ChatState.fromSnapshot(ChatSnapshot snapshot) => ChatState(
    messages: snapshot.messages,
    canSend: snapshot.canSend,
    maxChars: snapshot.maxChars,
    closesAt: snapshot.closesAt,
    hasMore: snapshot.hasMore,
    unread: snapshot.unread,
  );

  /// A fresh latest page: replaces messages it contains (read marks changed),
  /// adds new ones, keeps older pages already loaded and local messages that
  /// the server did not confirm yet.
  ChatState withFreshPage(ChatSnapshot fresh) {
    final freshIds = {for (final m in fresh.messages) m.id};
    final firstFresh = fresh.messages.isEmpty
        ? null
        : fresh.messages
              .map((m) => m.createdAt)
              .reduce((a, b) => a.isBefore(b) ? a : b);
    final older = [
      for (final m in messages)
        if (!m.isLocal &&
            !freshIds.contains(m.id) &&
            (firstFresh == null || m.createdAt.isBefore(firstFresh)))
          m,
    ];
    final confirmedKeys = {for (final m in fresh.messages) m.id};
    final locals = [
      for (final m in messages)
        if (m.isLocal && !confirmedKeys.contains(m.id)) m,
    ];
    return ChatState(
      messages: [...older, ...fresh.messages, ...locals],
      canSend: fresh.canSend,
      maxChars: fresh.maxChars,
      closesAt: fresh.closesAt,
      hasMore: older.isEmpty ? fresh.hasMore : hasMore,
      loadingMore: loadingMore,
      unread: fresh.unread,
      sendError: sendError,
    );
  }

  /// An older page loaded by scrolling back.
  ChatState withOlderPage(ChatSnapshot older) {
    final known = {for (final m in messages) m.id};
    return copyWith(
      messages: [
        ...older.messages.where((m) => !known.contains(m.id)),
        ...messages,
      ],
      hasMore: older.hasMore,
      loadingMore: false,
    );
  }

  ChatState withLocal(ChatMessage local) =>
      copyWith(messages: [...messages, local], clearSendError: true);

  /// The server stored a local message: swap it for the confirmed one (unless a refresh already added it).
  ChatState withConfirmed(String clientKey, ChatMessage confirmed) {
    final alreadyThere = messages.any((m) => m.id == confirmed.id);
    return copyWith(
      messages: [
        for (final m in messages)
          if (m.clientKey != clientKey) m,
        if (!alreadyThere) confirmed,
      ],
      clearSendError: true,
    );
  }

  ChatState withFailed(String clientKey, {String? error}) => copyWith(
    messages: [
      for (final m in messages) m.clientKey == clientKey ? m.failed() : m,
    ],
    sendError: error,
  );

  ChatState withSending(String clientKey) => copyWith(
    messages: [
      for (final m in messages) m.clientKey == clientKey ? m.sending() : m,
    ],
    clearSendError: true,
  );

  ChatState without(String clientKey) => copyWith(
    messages: [
      for (final m in messages)
        if (m.clientKey != clientKey) m,
    ],
    clearSendError: true,
  );

  ChatState markOwnRead(DateTime at) => copyWith(
    messages: [
      for (final m in messages)
        m.mine && !m.isLocal && m.readAt == null
            ? m.copyWith(delivery: ChatDelivery.read, readAt: at)
            : m,
    ],
  );
}
