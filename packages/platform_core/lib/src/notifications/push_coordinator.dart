import 'dart:async';

import 'package:flutter/foundation.dart';

import 'deep_link_handler.dart';
import 'push_gateway.dart';
import 'push_registrar.dart';

/// Runs push for a signed-in session: initialises the gateway (gracefully when
/// Firebase is not configured), asks for permission only through [enable] (after
/// the app's rationale), registers the token, shows foreground pushes as system
/// notifications and routes taps through [DeepLinkHandler].
class PushCoordinator {
  PushCoordinator({
    required this.gateway,
    required this.registrar,
    required this.links,
    this.onForeground,
  });

  final PushGateway gateway;
  final PushRegistrar registrar;
  final DeepLinkHandler links;

  /// Called when a push arrives while the app is open (refresh the inbox badge).
  final void Function()? onForeground;

  final ValueNotifier<PushStatus?> status = ValueNotifier(null);
  final _subs = <StreamSubscription<Object?>>[];

  Future<void> start() async {
    if (status.value != null) return;
    final initial = await gateway.initialize();
    status.value = initial;
    // Register even without push so the device (and its language) is known.
    await registrar.start();
    if (initial == PushStatus.notConfigured) return;
    _subs
      ..add(
        gateway.foregroundMessages.listen((m) async {
          onForeground?.call();
          await gateway.showLocal(m);
        }),
      )
      ..add(gateway.opened.listen(links.handle));
    final launch = await gateway.initialMessage();
    if (launch != null) unawaited(links.handle(launch));
  }

  /// After the in-app rationale: asks the OS and registers the token.
  Future<void> enable() async {
    status.value = await gateway.requestPermission();
    if (status.value == PushStatus.ready) await registrar.start();
  }

  Future<void> stop() async {
    for (final s in _subs) {
      await s.cancel();
    }
    _subs.clear();
    await registrar.stop();
    await gateway.deleteToken();
    status.value = null;
  }

  void dispose() {
    for (final s in _subs) {
      unawaited(s.cancel());
    }
    status.dispose();
  }
}
