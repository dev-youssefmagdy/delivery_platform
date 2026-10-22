import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import 'notification_models.dart';

/// Inbox, settings and device registration (docs/API.md notifications).
/// Screens depend on this contract; [ApiNotificationRepository] is the
/// implementation over [NotificationRemoteDataSource].
abstract interface class NotificationRepository {
  Future<NotificationPage> list({int page = 1, bool unreadOnly = false});
  Future<AppNotification> markRead(String id);
  Future<void> markAllRead();
  Future<NotificationPreferences> preferences();
  Future<NotificationPreferences> updatePreferences(List<PreferenceChange> changes);
  Future<void> registerDevice({
    required String deviceId,
    required String platform,
    required String locale,
    String? appVersion,
    String? pushToken,
  });
}

/// The only code that knows the notification endpoints.
class NotificationRemoteDataSource {
  const NotificationRemoteDataSource(this.api);

  final ApiClient api;

  Future<ApiResult<Object?>> list(int page, bool unreadOnly) => api.get(
    '/me/notifications',
    query: {'page': page, if (unreadOnly) 'unread': 1},
  );

  Future<ApiResult<Object?>> markRead(String id) =>
      api.post('/me/notifications/$id/read');

  Future<ApiResult<Object?>> markAllRead() =>
      api.post('/me/notifications/read-all');

  Future<ApiResult<Object?>> preferences() =>
      api.get('/me/notification-preferences');

  Future<ApiResult<Object?>> updatePreferences(List<Map<String, dynamic>> changes) =>
      api.put('/me/notification-preferences', body: {'preferences': changes});

  Future<ApiResult<Object?>> registerDevice(Map<String, dynamic> body) =>
      api.post('/me/devices', body: body);
}

class ApiNotificationRepository implements NotificationRepository {
  const ApiNotificationRepository(this._remote);

  final NotificationRemoteDataSource _remote;

  Map<String, dynamic> _map(ApiResult<Object?> r) {
    final data = r.data;
    if (data is! Map) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return Map<String, dynamic>.from(data);
  }

  @override
  Future<NotificationPage> list({int page = 1, bool unreadOnly = false}) async {
    final r = await _remote.list(page, unreadOnly);
    final data = r.data;
    final pagination = r.meta['pagination'];
    return NotificationPage(
      items: [
        for (final item in (data is List ? data : const []))
          AppNotification.fromJson(Map<String, dynamic>.from(item as Map)),
      ],
      currentPage: pagination is Map ? (pagination['current_page'] as int? ?? page) : page,
      lastPage: pagination is Map ? (pagination['last_page'] as int? ?? page) : page,
      unreadCount: r.meta['unread_count'] as int? ?? 0,
    );
  }

  @override
  Future<AppNotification> markRead(String id) async =>
      AppNotification.fromJson(_map(await _remote.markRead(id)));

  @override
  Future<void> markAllRead() async {
    await _remote.markAllRead();
  }

  @override
  Future<NotificationPreferences> preferences() async =>
      NotificationPreferences.fromJson(_map(await _remote.preferences()));

  @override
  Future<NotificationPreferences> updatePreferences(
    List<PreferenceChange> changes,
  ) async => NotificationPreferences.fromJson(
    _map(await _remote.updatePreferences([for (final c in changes) c.toJson()])),
  );

  @override
  Future<void> registerDevice({
    required String deviceId,
    required String platform,
    required String locale,
    String? appVersion,
    String? pushToken,
  }) async {
    await _remote.registerDevice({
      'device_id': deviceId,
      'platform': platform,
      'locale': locale,
      'app_version': ?appVersion,
      'push_token': ?pushToken,
    });
  }
}
