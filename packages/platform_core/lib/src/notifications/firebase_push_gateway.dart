import 'dart:async';
import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'push_gateway.dart';

/// FCM implementation of [PushGateway]. Firebase config files
/// (google-services.json, GoogleService-Info.plist) are NOT in git; without
/// them [initialize] answers [PushStatus.notConfigured] and nothing crashes.
class FirebasePushGateway implements PushGateway {
  FirebasePushGateway();

  static const channelId = 'platform_default';

  final _local = FlutterLocalNotificationsPlugin();
  final _foreground = StreamController<PushMessageData>.broadcast();
  final _opened = StreamController<PushMessageData>.broadcast();
  bool _ready = false;
  FirebaseMessaging? _messaging;

  @override
  Future<PushStatus> initialize() async {
    if (kIsWeb) return PushStatus.notConfigured;
    try {
      if (Firebase.apps.isEmpty) await Firebase.initializeApp();
      _messaging = FirebaseMessaging.instance;
      await _local.initialize(
        settings: const InitializationSettings(
          android: AndroidInitializationSettings('@mipmap/ic_launcher'),
          iOS: DarwinInitializationSettings(
            requestAlertPermission: false,
            requestBadgePermission: false,
            requestSoundPermission: false,
          ),
        ),
        onDidReceiveNotificationResponse: (response) {
          final payload = response.payload;
          if (payload == null || payload.isEmpty) return;
          _opened.add(_decode(payload));
        },
      );
      await _local
          .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin
          >()
          ?.createNotificationChannel(
            const AndroidNotificationChannel(
              channelId,
              'Notifications',
              importance: Importance.high,
            ),
          );
      FirebaseMessaging.onMessage.listen((m) {
        final data = PushMessageData.fromData(
          m.data,
          title: m.notification?.title,
          body: m.notification?.body,
        );
        _foreground.add(data);
      });
      FirebaseMessaging.onMessageOpenedApp.listen(
        (m) => _opened.add(
          PushMessageData.fromData(
            m.data,
            title: m.notification?.title,
            body: m.notification?.body,
          ),
        ),
      );
      _ready = true;
      return _statusOf(await _messaging!.getNotificationSettings());
    } on Object catch (e) {
      // Missing config file, unsupported platform or play services problem.
      debugPrint('Push unavailable: ${e.runtimeType}');
      return PushStatus.notConfigured;
    }
  }

  PushStatus _statusOf(NotificationSettings s) =>
      switch (s.authorizationStatus) {
        AuthorizationStatus.authorized ||
        AuthorizationStatus.provisional => PushStatus.ready,
        AuthorizationStatus.denied ||
        AuthorizationStatus.deniedPermanently => PushStatus.denied,
        AuthorizationStatus.notDetermined => PushStatus.permissionNeeded,
      };

  @override
  Future<PushStatus> requestPermission() async {
    if (!_ready) return PushStatus.notConfigured;
    try {
      return _statusOf(await _messaging!.requestPermission());
    } on Object {
      return PushStatus.failed;
    }
  }

  @override
  Future<String?> token() async {
    if (!_ready) return null;
    try {
      return await _messaging!.getToken();
    } on Object {
      return null;
    }
  }

  @override
  Stream<String> get tokenRefreshes => _ready
      ? _messaging!.onTokenRefresh
      : const Stream<String>.empty();

  @override
  Stream<PushMessageData> get foregroundMessages => _foreground.stream;

  @override
  Stream<PushMessageData> get opened => _opened.stream;

  @override
  Future<PushMessageData?> initialMessage() async {
    if (!_ready) return null;
    try {
      final m = await _messaging!.getInitialMessage();
      return m == null
          ? null
          : PushMessageData.fromData(
              m.data,
              title: m.notification?.title,
              body: m.notification?.body,
            );
    } on Object {
      return null;
    }
  }

  @override
  Future<void> showLocal(PushMessageData message) async {
    if (!_ready) return;
    await _local.show(
      id: message.notificationId.hashCode & 0x7fffffff,
      title: message.title,
      body: message.body,
      notificationDetails: const NotificationDetails(
        android: AndroidNotificationDetails(
          channelId,
          'Notifications',
          importance: Importance.high,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
      ),
      payload: _encode(message),
    );
  }

  @override
  Future<void> deleteToken() async {
    if (!_ready) return;
    try {
      await _messaging!.deleteToken();
    } on Object {
      // Best effort: the server also clears the token on logout.
    }
  }

  String _encode(PushMessageData m) => jsonEncode({
    'notification_id': m.notificationId,
    'link_type': m.link?.type.value,
    'link_id': m.link?.id,
  });

  PushMessageData _decode(String payload) {
    final map = jsonDecode(payload);
    return PushMessageData.fromData(
      map is Map ? Map<String, dynamic>.from(map) : const {},
    );
  }
}
