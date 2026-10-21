import 'dart:async';

import 'package:dart_pusher_channels/dart_pusher_channels.dart';

import '../config/env.dart';

class RealtimeEvent {
  const RealtimeEvent({
    required this.channel,
    required this.name,
    required this.data,
  });

  final String channel;
  final String name;
  final Map<String, dynamic> data;

  /// Monotonic per-entity counter set by the server, when present.
  int? get sequence => data['sequence'] as int?;
}

/// Where the realtime connection stands, for the state indicator in the apps.
enum RealtimeConnectionState { disconnected, connecting, connected }

/// Realtime events are hints only; callers refetch authoritative state.
abstract interface class RealtimeClient {
  /// Emits on every change of the connection (connected, dropped, retrying).
  Stream<RealtimeConnectionState> get connectionState;

  /// Emits after the first connect and after every reconnect. Subscribers must
  /// refetch snapshots (events may have been missed while offline).
  Stream<void> get connected;

  Future<void> connect();

  /// Subscribes to a private channel (the `private-` prefix is added).
  Future<Stream<RealtimeEvent>> subscribePrivate(String channel);

  void unsubscribe(String channel);

  Future<void> disconnect();
}

/// Laravel Reverb (Pusher protocol) client with private-channel authorization
/// against `POST {origin}/broadcasting/auth` using the bearer token.
class PusherRealtimeClient implements RealtimeClient {
  PusherRealtimeClient({required this._env, required this._readToken});

  final Env _env;
  final Future<String?> Function() _readToken;
  PusherChannelsClient? _client;
  final _channels = <String, PrivateChannel>{};
  final _connected = StreamController<void>.broadcast();
  final _state = StreamController<RealtimeConnectionState>.broadcast();
  StreamSubscription<PusherChannelsClientLifeCycleState>? _lifecycleSub;
  StreamSubscription<void>? _connectedSub;

  @override
  Stream<void> get connected => _connected.stream;

  @override
  Stream<RealtimeConnectionState> get connectionState => _state.stream;

  PusherChannelsClient _ensureClient() {
    return _client ??= () {
      final secure = _env.reverbScheme == 'https' || _env.reverbScheme == 'wss';
      final client = PusherChannelsClient.websocket(
        options: PusherChannelsOptions.fromHost(
          scheme: secure ? 'wss' : 'ws',
          host: _env.reverbHost,
          port: _env.reverbPort,
          key: _env.reverbKey,
          shouldSupplyMetadataQueries: true,
          metadata: PusherChannelsOptionsMetadata.byDefault(),
        ),
        connectionErrorHandler: (exception, trace, refresh) async {
          await Future<void>.delayed(const Duration(seconds: 2));
          refresh();
        },
      );
      _connectedSub = client.onConnectionEstablished.listen(
        (_) => _connected.add(null),
      );
      _lifecycleSub = client.lifecycleStream.listen((event) {
        final state = switch (event) {
          PusherChannelsClientLifeCycleState.establishedConnection =>
            RealtimeConnectionState.connected,
          PusherChannelsClientLifeCycleState.pendingConnection ||
          PusherChannelsClientLifeCycleState.reconnecting =>
            RealtimeConnectionState.connecting,
          PusherChannelsClientLifeCycleState.connectionError ||
          PusherChannelsClientLifeCycleState.disconnected ||
          PusherChannelsClientLifeCycleState.disposed =>
            RealtimeConnectionState.disconnected,
          _ => null,
        };
        if (state != null) _state.add(state);
      });
      return client;
    }();
  }

  @override
  Future<void> connect() => _ensureClient().connect();

  @override
  Future<Stream<RealtimeEvent>> subscribePrivate(String channel) async {
    final client = _ensureClient();
    final name = 'private-$channel';
    final existing = _channels[name];
    existing?.unsubscribe();
    final token = await _readToken();
    final ch = client.privateChannel(
      name,
      authorizationDelegate:
          EndpointAuthorizableChannelTokenAuthorizationDelegate.forPrivateChannel(
            authorizationEndpoint: Uri.parse(
              '${_env.apiBaseUrl.replaceAll(RegExp(r'/+$'), '')}/broadcasting/auth',
            ),
            headers: {
              'Accept': 'application/json',
              if (token != null) 'Authorization': 'Bearer $token',
            },
            onAuthFailed: (_, _) {},
          ),
    );
    _channels[name] = ch;
    ch.subscribeIfNotUnsubscribed();
    return ch.bindToAll().map(
      (e) => RealtimeEvent(
        channel: channel,
        name: e.name,
        data: e.tryGetDataAsMap() ?? const {},
      ),
    );
  }

  @override
  void unsubscribe(String channel) {
    _channels.remove('private-$channel')?.unsubscribe();
  }

  @override
  Future<void> disconnect() async {
    await _client?.disconnect();
  }

  void dispose() {
    _connectedSub?.cancel();
    _lifecycleSub?.cancel();
    _connected.close();
    _state.close();
    _client?.dispose();
  }
}
