import '../api/api_exception.dart';
import 'notification_models.dart';
import 'push_gateway.dart';

enum DeepLinkOutcome {
  /// Went to the screen the notification is about.
  opened,

  /// The target is gone, closed to this account, or has no screen yet: the inbox is shown with a notice.
  unavailable,
}

/// Turns a tap on a notification into navigation (SRS section 21): validates that the target still exists for this
/// account, and falls back to the inbox with a notice instead of a broken screen. App-specific parts are injected.
class DeepLinkHandler {
  DeepLinkHandler({
    required this.routeFor,
    required this.validate,
    required this.open,
    required this.openInbox,
    required this.onUnavailable,
    required this.markRead,
  });

  /// Screen route for a link, or null when this app has no screen for it (yet).
  final String? Function(NotificationLink link) routeFor;

  /// Whether the target can still be opened; may throw [ApiException].
  final Future<bool> Function(NotificationLink link) validate;
  final void Function(String route) open;
  final void Function() openInbox;
  final void Function() onUnavailable;
  final Future<void> Function(String notificationId) markRead;

  Future<DeepLinkOutcome> handle(PushMessageData message) async {
    final id = message.notificationId;
    if (id != null) {
      try {
        await markRead(id);
      } on Object {
        // Reading is best effort; navigation must not depend on it.
      }
    }
    final link = message.link;
    final route = link == null ? null : routeFor(link);
    if (link == null || route == null) {
      openInbox();
      return DeepLinkOutcome.opened;
    }
    var valid = true;
    try {
      valid = await validate(link);
    } on ApiException catch (e) {
      // Offline or server trouble: open the screen, it has its own error state.
      valid = !(e.statusCode == 404 || e.statusCode == 403);
    }
    if (!valid) {
      openInbox();
      onUnavailable();
      return DeepLinkOutcome.unavailable;
    }
    open(route);
    return DeepLinkOutcome.opened;
  }
}
