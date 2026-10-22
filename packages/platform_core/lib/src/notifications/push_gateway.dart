import 'notification_models.dart';

/// Where push stands on this device. Every state has a UI (Plan 6.2).
enum PushStatus {
  /// Firebase config files are not in the build (documented in SETUP.md): the app works without push.
  notConfigured,

  /// Not asked yet: show the rationale, then [PushGateway.requestPermission].
  permissionNeeded,
  denied,
  ready,
  failed,
}

/// A push the user can act on.
class PushMessageData {
  const PushMessageData({
    this.notificationId,
    this.title,
    this.body,
    this.link,
  });

  final String? notificationId;
  final String? title;
  final String? body;
  final NotificationLink? link;

  /// Reads the backend payload (`notification_id`, `link_type`, `link_id`).
  factory PushMessageData.fromData(
    Map<String, dynamic> data, {
    String? title,
    String? body,
  }) => PushMessageData(
    notificationId: data['notification_id'] as String?,
    title: title,
    body: body,
    link: NotificationLink.fromPushData(data),
  );
}

/// Platform push (FCM/APNs) behind a contract so the app logic never touches
/// Firebase and degrades cleanly when it is not configured.
abstract interface class PushGateway {
  /// Initialises the platform; never throws. Returns the resulting status.
  Future<PushStatus> initialize();

  Future<PushStatus> requestPermission();

  /// Current device token, null when unavailable.
  Future<String?> token();

  Stream<String> get tokenRefreshes;

  /// A push that arrived while the app is open.
  Stream<PushMessageData> get foregroundMessages;

  /// A notification the user tapped (app in background or opened from it).
  Stream<PushMessageData> get opened;

  /// The notification that launched the app from a terminated state, once.
  Future<PushMessageData?> initialMessage();

  /// Shows [message] as a system notification while the app is open.
  Future<void> showLocal(PushMessageData message);

  /// Forgets the token on sign-out.
  Future<void> deleteToken();
}
