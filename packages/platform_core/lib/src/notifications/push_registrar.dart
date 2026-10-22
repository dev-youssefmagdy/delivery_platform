import 'dart:async';

import 'notification_repository.dart';
import 'push_gateway.dart';

/// Keeps the server's copy of this device's push token fresh (`POST /me/devices`):
/// registers after sign-in, again whenever the token rotates, and once without a
/// token when push is unavailable so the device still shows up with its locale.
class PushRegistrar {
  PushRegistrar({
    required this.gateway,
    required this.repository,
    required this.deviceId,
    required this.platform,
    required this.appVersion,
    required this.languageCode,
  });

  final PushGateway gateway;
  final NotificationRepository repository;
  final String Function() deviceId;
  final String platform;
  final Future<String> Function() appVersion;
  final String Function() languageCode;

  StreamSubscription<String>? _refresh;
  String? _lastSent;

  /// Registers now and follows token rotation. Never throws.
  Future<void> start() async {
    await register(await gateway.token());
    await _refresh?.cancel();
    _refresh = gateway.tokenRefreshes.listen(register);
  }

  Future<void> register(String? token) async {
    if (token != null && token == _lastSent) return;
    try {
      await repository.registerDevice(
        deviceId: deviceId(),
        platform: platform,
        locale: languageCode(),
        appVersion: await appVersion(),
        pushToken: token,
      );
      _lastSent = token;
    } on Object {
      // Retried at the next start or token refresh; push is never blocking.
    }
  }

  Future<void> stop() async {
    await _refresh?.cancel();
    _refresh = null;
    _lastSent = null;
  }
}
