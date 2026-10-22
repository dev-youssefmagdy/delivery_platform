/// Models of booking chat and support tickets (docs/API.md "Chat" and "Support").
/// Parsing is tolerant: unknown values never crash a screen.
library;

DateTime _date(Object? raw) =>
    DateTime.tryParse(raw as String? ?? '')?.toLocal() ??
    DateTime.fromMillisecondsSinceEpoch(0);

DateTime? _dateOrNull(Object? raw) =>
    raw is String ? DateTime.tryParse(raw)?.toLocal() : null;

/// Which side of the conversation the signed-in person is.
enum ChatRole {
  customer('customer'),
  provider('provider');

  const ChatRole(this.value);
  final String value;

  static ChatRole parse(String? raw) =>
      raw == 'provider' ? ChatRole.provider : ChatRole.customer;
}

class ChatAttachment {
  const ChatAttachment({required this.mime, required this.sizeBytes, required this.url});

  final String mime;
  final int sizeBytes;

  /// Short-lived signed URL; downloading it still needs the person's token.
  final String url;

  factory ChatAttachment.fromJson(Map<String, dynamic> json) => ChatAttachment(
    mime: json['mime'] as String? ?? 'image/jpeg',
    sizeBytes: json['size_bytes'] as int? ?? 0,
    url: json['url'] as String? ?? '',
  );
}

/// Delivery state of a message in the chat screen. `sent` and `read` come from
/// the server; `sending` and `failed` only exist locally (optimistic send).
enum ChatDelivery { sending, failed, sent, read }

class ChatMessage {
  const ChatMessage({
    required this.id,
    required this.mine,
    required this.body,
    required this.createdAt,
    this.attachment,
    this.readAt,
    this.purged = false,
    this.delivery = ChatDelivery.sent,
    this.localAttachmentPath,
    this.clientKey,
  });

  final String id;
  final bool mine;
  final String? body;
  final DateTime createdAt;
  final ChatAttachment? attachment;
  final DateTime? readAt;
  final bool purged;
  final ChatDelivery delivery;

  /// Picked image of a message that is still sending or failed.
  final String? localAttachmentPath;

  /// The Idempotency-Key of a local message: a retry reuses it.
  final String? clientKey;

  bool get isLocal => delivery == ChatDelivery.sending || delivery == ChatDelivery.failed;

  ChatMessage copyWith({ChatDelivery? delivery, DateTime? readAt}) => ChatMessage(
    id: id,
    mine: mine,
    body: body,
    createdAt: createdAt,
    attachment: attachment,
    readAt: readAt ?? this.readAt,
    purged: purged,
    delivery: delivery ?? this.delivery,
    localAttachmentPath: localAttachmentPath,
    clientKey: clientKey,
  );

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    final attachment = json['attachment'];
    final readAt = _dateOrNull(json['read_at']);
    return ChatMessage(
      id: json['id'] as String,
      mine: json['mine'] as bool? ?? false,
      body: json['body'] as String?,
      createdAt: _date(json['created_at']),
      attachment: attachment is Map ? ChatAttachment.fromJson(Map<String, dynamic>.from(attachment)) : null,
      readAt: readAt,
      purged: json['purged'] as bool? ?? false,
      delivery: readAt != null ? ChatDelivery.read : ChatDelivery.sent,
    );
  }

  /// A message the person just wrote, shown before the server answers.
  factory ChatMessage.local({
    required String clientKey,
    String? body,
    String? localAttachmentPath,
  }) => ChatMessage(
    id: 'local-$clientKey',
    mine: true,
    body: body,
    createdAt: DateTime.now(),
    delivery: ChatDelivery.sending,
    localAttachmentPath: localAttachmentPath,
    clientKey: clientKey,
  );

  /// Same message marked failed (kept so the person can retry or discard it).
  ChatMessage failed() => copyWith(delivery: ChatDelivery.failed);

  ChatMessage sending() => copyWith(delivery: ChatDelivery.sending);
}

/// `GET /bookings/{id}/messages` (and the provider twin).
class ChatSnapshot {
  const ChatSnapshot({
    required this.messages,
    required this.role,
    required this.canSend,
    required this.unread,
    required this.hasMore,
    required this.maxChars,
    this.closesAt,
  });

  final List<ChatMessage> messages;
  final ChatRole role;
  final bool canSend;
  final int unread;
  final bool hasMore;
  final int maxChars;
  final DateTime? closesAt;
}

class TicketCategoryOption {
  const TicketCategoryOption({required this.value, required this.label});

  final String value;
  final String label;

  factory TicketCategoryOption.fromJson(Map<String, dynamic> json) => TicketCategoryOption(
    value: json['value'] as String? ?? 'other',
    label: json['label'] as String? ?? '',
  );
}

class TicketAttachment {
  const TicketAttachment({
    required this.id,
    required this.mime,
    required this.sizeBytes,
    required this.url,
    this.name,
  });

  final String id;
  final String mime;
  final int sizeBytes;
  final String url;
  final String? name;

  bool get isImage => mime.startsWith('image/');

  factory TicketAttachment.fromJson(Map<String, dynamic> json) => TicketAttachment(
    id: json['id'] as String? ?? '',
    mime: json['mime'] as String? ?? '',
    sizeBytes: json['size_bytes'] as int? ?? 0,
    url: json['url'] as String? ?? '',
    name: json['name'] as String?,
  );
}

class TicketMessage {
  const TicketMessage({
    required this.id,
    required this.fromSupport,
    required this.body,
    required this.createdAt,
    this.attachments = const [],
  });

  final String id;
  final bool fromSupport;
  final String body;
  final DateTime createdAt;
  final List<TicketAttachment> attachments;

  factory TicketMessage.fromJson(Map<String, dynamic> json) => TicketMessage(
    id: json['id'] as String? ?? '',
    fromSupport: json['from'] == 'support',
    body: json['body'] as String? ?? '',
    createdAt: _date(json['created_at']),
    attachments: [
      for (final a in (json['attachments'] is List ? json['attachments'] as List : const []))
        TicketAttachment.fromJson(Map<String, dynamic>.from(a as Map)),
    ],
  );
}

/// The status values the apps style; unknown values fall back to [open].
enum TicketStatusView {
  open('open'),
  inProgress('in_progress'),
  waitingUser('waiting_user'),
  escalated('escalated'),
  resolved('resolved'),
  closed('closed');

  const TicketStatusView(this.value);
  final String value;

  static TicketStatusView parse(String? raw) =>
      TicketStatusView.values.firstWhere((s) => s.value == raw, orElse: () => TicketStatusView.open);

  bool get isFinished => this == resolved || this == closed;
}

class SupportTicket {
  const SupportTicket({
    required this.id,
    required this.number,
    required this.category,
    required this.categoryLabel,
    required this.status,
    required this.statusLabel,
    required this.subject,
    required this.canReply,
    required this.createdAt,
    this.bookingId,
    this.bookingCode,
    this.resolution,
    this.lastMessageAt,
    this.messages,
  });

  final String id;
  final String number;
  final String category;
  final String categoryLabel;
  final TicketStatusView status;
  final String statusLabel;
  final String subject;
  final bool canReply;
  final DateTime createdAt;
  final String? bookingId;
  final String? bookingCode;
  final String? resolution;
  final DateTime? lastMessageAt;

  /// Present on the detail response only; staff-only notes are never part of it.
  final List<TicketMessage>? messages;

  factory SupportTicket.fromJson(Map<String, dynamic> json) {
    final messages = json['messages'];
    return SupportTicket(
      id: json['id'] as String,
      number: json['number'] as String? ?? '',
      category: json['category'] as String? ?? 'other',
      categoryLabel: json['category_label'] as String? ?? '',
      status: TicketStatusView.parse(json['status'] as String?),
      statusLabel: json['status_label'] as String? ?? '',
      subject: json['subject'] as String? ?? '',
      canReply: json['can_reply'] as bool? ?? false,
      createdAt: _date(json['created_at']),
      bookingId: json['booking_id'] as String?,
      bookingCode: json['booking_code'] as String?,
      resolution: json['resolution'] as String?,
      lastMessageAt: _dateOrNull(json['last_message_at']),
      messages: messages is List
          ? [for (final m in messages) TicketMessage.fromJson(Map<String, dynamic>.from(m as Map))]
          : null,
    );
  }
}

class TicketPage {
  const TicketPage({required this.items, required this.currentPage, required this.lastPage});

  final List<SupportTicket> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}

/// What the person typed when opening a ticket. Files are local paths.
class NewTicketInput {
  const NewTicketInput({
    required this.category,
    required this.subject,
    required this.body,
    this.bookingId,
    this.paymentId,
    this.payoutId,
    this.attachmentPaths = const [],
  });

  /// Money tickets link the payment or payout the person is asking about (ids they own).
  final String? paymentId;
  final String? payoutId;
  final String category;
  final String subject;
  final String body;
  final String? bookingId;
  final List<String> attachmentPaths;
}
