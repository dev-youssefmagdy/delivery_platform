import '../enums/enums.dart';

/// What a tap on a notification opens. [id] may be null for list-level targets.
class NotificationLink {
  const NotificationLink({required this.type, this.id});

  final NotificationLinkType type;
  final String? id;

  factory NotificationLink.fromJson(Map<String, dynamic> json) =>
      NotificationLink(
        type: NotificationLinkType.parse(json['type'] as String?),
        id: json['id'] as String?,
      );

  /// Builds a link from a push `data` payload (`link_type`, `link_id`).
  static NotificationLink? fromPushData(Map<String, dynamic> data) {
    final type = data['link_type'];
    if (type is! String || type.isEmpty) return null;
    final id = data['link_id'];
    return NotificationLink(
      type: NotificationLinkType.parse(type),
      id: id is String && id.isNotEmpty ? id : null,
    );
  }
}

/// One inbox entry (`GET /me/notifications`).
class AppNotification {
  const AppNotification({
    required this.id,
    required this.event,
    required this.category,
    required this.title,
    required this.body,
    required this.read,
    required this.createdAt,
    this.link,
  });

  final String id;
  final String event;
  final NotificationCategory category;
  final String title;
  final String body;
  final bool read;
  final DateTime createdAt;
  final NotificationLink? link;

  AppNotification asRead() => AppNotification(
    id: id,
    event: event,
    category: category,
    title: title,
    body: body,
    read: true,
    createdAt: createdAt,
    link: link,
  );

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    final link = json['link'];
    return AppNotification(
      id: json['id'] as String,
      event: json['event'] as String? ?? '',
      category: NotificationCategory.parse(json['category'] as String?),
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      read: json['read'] as bool? ?? false,
      createdAt:
          DateTime.tryParse(json['created_at'] as String? ?? '') ??
          DateTime.fromMillisecondsSinceEpoch(0),
      link: link is Map
          ? NotificationLink.fromJson(Map<String, dynamic>.from(link))
          : null,
    );
  }
}

class NotificationPage {
  const NotificationPage({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.unreadCount,
  });

  final List<AppNotification> items;
  final int currentPage;
  final int lastPage;
  final int unreadCount;

  bool get hasMore => currentPage < lastPage;
}

class ChannelPreference {
  const ChannelPreference({
    required this.channel,
    required this.enabled,
    required this.locked,
  });

  final NotificationChannel channel;
  final bool enabled;

  /// True when the server keeps the channel on (essential categories).
  final bool locked;

  factory ChannelPreference.fromJson(Map<String, dynamic> json) =>
      ChannelPreference(
        channel: NotificationChannel.parse(json['channel'] as String?),
        enabled: json['enabled'] as bool? ?? false,
        locked: json['locked'] as bool? ?? false,
      );
}

class CategoryPreference {
  const CategoryPreference({
    required this.category,
    required this.label,
    required this.description,
    required this.essential,
    required this.optInRequired,
    required this.channels,
  });

  final NotificationCategory category;
  final String label;
  final String description;
  final bool essential;
  final bool optInRequired;
  final List<ChannelPreference> channels;

  factory CategoryPreference.fromJson(Map<String, dynamic> json) =>
      CategoryPreference(
        category: NotificationCategory.parse(json['category'] as String?),
        label: json['label'] as String? ?? '',
        description: json['description'] as String? ?? '',
        essential: json['essential'] as bool? ?? false,
        optInRequired: json['opt_in_required'] as bool? ?? false,
        channels: [
          for (final c in (json['channels'] as List? ?? const []))
            ChannelPreference.fromJson(Map<String, dynamic>.from(c as Map)),
        ],
      );
}

class NotificationPreferences {
  const NotificationPreferences(this.categories);

  final List<CategoryPreference> categories;

  factory NotificationPreferences.fromJson(Map<String, dynamic> json) =>
      NotificationPreferences([
        for (final c in (json['categories'] as List? ?? const []))
          CategoryPreference.fromJson(Map<String, dynamic>.from(c as Map)),
      ]);
}

/// One requested change of a preference.
class PreferenceChange {
  const PreferenceChange(this.category, this.channel, this.enabled);

  final NotificationCategory category;
  final NotificationChannel channel;
  final bool enabled;

  Map<String, dynamic> toJson() => {
    'category': category.value,
    'channel': channel.value,
    'enabled': enabled,
  };
}
