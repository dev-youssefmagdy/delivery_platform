import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../features/auth/presentation/providers/auth_providers.dart';
import 'providers.dart';

final realtimeClientProvider = Provider<RealtimeClient>((ref) {
  final session = ref.watch(sessionControllerProvider);
  final client = PusherRealtimeClient(
    env: ref.watch(envProvider),
    readToken: () async => session.readToken(),
  );
  ref.onDispose(client.dispose);
  return client;
});

/// The signed-in customer's private channel (`private-customer.{uuid}`):
/// offer, status and location events. Events are hints; consumers refetch
/// the snapshot (Plan 6.2). One subscription is shared by every consumer.
class CustomerEvents {
  CustomerEvents._();

  final _events = StreamController<RealtimeEvent>.broadcast();
  final _reconnected = StreamController<void>.broadcast();
  final _state = StreamController<RealtimeConnectionState>.broadcast();
  RealtimeConnectionState current = RealtimeConnectionState.disconnected;

  Stream<RealtimeEvent> get events => _events.stream;

  /// Fires after the first connect and every reconnect: refetch snapshots.
  Stream<void> get reconnected => _reconnected.stream;

  Stream<RealtimeConnectionState> get state => _state.stream;

  bool get isLive => current == RealtimeConnectionState.connected;

  void _dispose() {
    _events.close();
    _reconnected.close();
    _state.close();
  }
}

/// Starts the realtime connection for the signed-in customer. Failure to
/// connect is not an error: the polling fallback keeps screens current.
final customerEventsProvider = Provider<CustomerEvents>((ref) {
  final hub = CustomerEvents._();
  ref.onDispose(hub._dispose);

  final customerId = ref.watch(meProvider.select((m) => m.value?.id));
  final env = ref.watch(envProvider);
  if (customerId == null || env.reverbKey.isEmpty) return hub;

  final client = ref.watch(realtimeClientProvider);
  final subs = <StreamSubscription<Object?>>[];
  ref.onDispose(() {
    for (final s in subs) {
      s.cancel();
    }
    client.unsubscribe('customer.$customerId');
  });

  subs.add(
    client.connectionState.listen((s) {
      hub.current = s;
      if (!hub._state.isClosed) hub._state.add(s);
    }),
  );
  subs.add(
    client.connected.listen((_) {
      if (!hub._reconnected.isClosed) hub._reconnected.add(null);
    }),
  );

  () async {
    try {
      await client.connect();
      final stream = await client.subscribePrivate('customer.$customerId');
      subs.add(
        stream.listen((e) {
          if (!hub._events.isClosed) hub._events.add(e);
        }),
      );
    } on Object {
      // Polling keeps working without the socket.
    }
  }();
  return hub;
});
