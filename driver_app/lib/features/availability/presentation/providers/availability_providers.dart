import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/providers.dart';

final availabilityRepositoryProvider = Provider<AvailabilityRepository>(
  (ref) => AvailabilityRepository(ref.watch(apiClientProvider)),
);

/// One-off position reads and permissions. Tests override it with a fake.
final locationServiceProvider = Provider<LocationService>(
  (ref) => const GeolocatorLocationService(),
);

/// The continuous position stream (an Android foreground service while it is
/// listened to). Tests override it with a fake.
final trackingSourceProvider = Provider<TrackingLocationSource>(
  (ref) => const GeolocatorTrackingSource(),
);

final realtimeClientProvider = Provider<RealtimeClient>((ref) {
  final session = ref.watch(sessionControllerProvider);
  final client = PusherRealtimeClient(
    env: ref.watch(envProvider),
    readToken: session.readToken,
  );
  ref.onDispose(client.dispose);
  return client;
});

/// Why going online did not happen, so the UI can say the right thing.
sealed class GoOnlineFailure {
  const GoOnlineFailure();
}

/// The driver declined or blocked location access, or the device has it off.
class PermissionRequired extends GoOnlineFailure {
  const PermissionRequired(this.state);

  final LocationPermissionState state;
}

class PositionUnavailable extends GoOnlineFailure {
  const PositionUnavailable();
}

/// The server's typed reasons (422 `provider_not_eligible`).
class NotEligible extends GoOnlineFailure {
  const NotEligible(this.eligibility);

  final EligibilityInfo eligibility;
}

/// Any other failure; [error] is usually an [ApiException]
/// (`outside_service_area`, `vehicle_not_usable`, offline ...).
class RequestFailed extends GoOnlineFailure {
  const RequestFailed(this.error);

  final Object error;
}

enum OfflineOutcome { done, needsConfirmation }

const _initial = AvailabilitySnapshot(
  availability: ProviderAvailability(online: false, tracking: TrackingPlan.off),
  eligibility: EligibilityInfo(eligible: false),
);

/// The driver's availability (`GET /provider/availability`) and its switch.
/// Writes replace the state with the server's answer; reads are cheap and run
/// again on every realtime reconnect and event.
class AvailabilityNotifier extends AsyncNotifier<AvailabilitySnapshot> {
  AvailabilityRepository get _repo => ref.read(availabilityRepositoryProvider);

  @override
  Future<AvailabilitySnapshot> build() async {
    final session = ref.watch(sessionStatusProvider);
    if (session != SessionStatus.authenticated) return _initial;
    return _repo.snapshot();
  }

  /// Re-reads the snapshot; a failed read keeps what is on screen.
  Future<void> refresh() async {
    final previous = state.value;
    final next = await AsyncValue.guard(_repo.snapshot);
    state = next.hasError && previous != null ? AsyncData(previous) : next;
  }

  /// Asks for location permission (showing [showRationale] first), reads the
  /// position and goes online. Returns null on success.
  Future<GoOnlineFailure?> goOnline({
    required Future<bool> Function() showRationale,
    String? vehicleId,
  }) async {
    final location = ref.read(locationServiceProvider);
    final permission = await LocationPermissionFlow(location)
        .ensure(showRationale: showRationale);
    if (permission != LocationPermissionState.granted) {
      return PermissionRequired(permission);
    }

    final GeoPoint? position;
    try {
      position = await location.current();
    } on Object {
      return const PositionUnavailable();
    }
    if (position == null) return const PositionUnavailable();

    try {
      final availability = await _repo.goOnline(
        location: position,
        vehicleId: vehicleId,
      );
      final previous = state.value ?? _initial;
      state = AsyncData(
        previous.copyWith(
          availability: availability,
          eligibility: const EligibilityInfo(eligible: true),
        ),
      );
      return null;
    } on ApiException catch (e) {
      if (e.code == 'provider_not_eligible') {
        final info = eligibilityFromMeta(e.meta);
        final previous = state.value ?? _initial;
        state = AsyncData(previous.copyWith(eligibility: info));
        return NotEligible(info);
      }
      return RequestFailed(e);
    }
  }

  /// Goes offline. With a job in progress the server asks for confirmation
  /// first ([OfflineOutcome.needsConfirmation]); call again with
  /// `confirm: true` once the driver agreed.
  Future<OfflineOutcome> goOffline({bool confirm = false}) async {
    try {
      final availability = await _repo.goOffline(confirm: confirm);
      final previous = state.value ?? _initial;
      state = AsyncData(previous.copyWith(availability: availability));
      return OfflineOutcome.done;
    } on ApiException catch (e) {
      if (e.code == 'active_job_blocks_offline') {
        return OfflineOutcome.needsConfirmation;
      }
      rethrow;
    }
  }
}

final availabilityProvider =
    AsyncNotifierProvider<AvailabilityNotifier, AvailabilitySnapshot>(
      AvailabilityNotifier.new,
      retry: (_, _) => null,
    );

/// What the tracker is doing, for the status line.
class TrackingStatus {
  const TrackingStatus({
    this.running = false,
    this.plan = TrackingPlan.off,
    this.lastUploadAt,
    this.lastUploadFailed = false,
    this.permission = LocationPermissionState.granted,
  });

  final bool running;
  final TrackingPlan plan;
  final DateTime? lastUploadAt;
  final bool lastUploadFailed;

  /// Set when tracking is wanted but the OS no longer allows it.
  final LocationPermissionState permission;

  TrackingStatus copyWith({
    bool? running,
    TrackingPlan? plan,
    DateTime? lastUploadAt,
    bool? lastUploadFailed,
    LocationPermissionState? permission,
  }) => TrackingStatus(
    running: running ?? this.running,
    plan: plan ?? this.plan,
    lastUploadAt: lastUploadAt ?? this.lastUploadAt,
    lastUploadFailed: lastUploadFailed ?? this.lastUploadFailed,
    permission: permission ?? this.permission,
  );
}

/// Foreground location tracking with a state-based frequency (SRS §7.2, §10.3):
/// the server's [TrackingPlan] says how often to upload (idle / to pickup /
/// active job) and whether to track at all. Offline with no job means the
/// position stream (and with it the Android foreground service) is stopped.
/// Uploads are buffered while the network is down (at most [maxBuffered]).
class TrackingController extends Notifier<TrackingStatus> {
  static const maxBuffered = 20;

  StreamSubscription<GeoPoint>? _subscription;
  Timer? _timer;
  final _buffer = <GeoPoint>[];
  GeoPoint? _latest;
  bool _flushing = false;
  int _generation = 0;

  @override
  TrackingStatus build() {
    ref.listen<AsyncValue<AvailabilitySnapshot>>(availabilityProvider, (
      _,
      next,
    ) {
      final plan = next.value?.availability.tracking;
      if (plan != null) _apply(plan);
    }, fireImmediately: true);
    ref.listen<SessionStatus>(sessionStatusProvider, (_, next) {
      if (next != SessionStatus.authenticated) _stop();
    });
    ref.onDispose(() {
      _generation++;
      _cancel();
    });
    return const TrackingStatus();
  }

  void _apply(TrackingPlan plan) {
    if (!plan.isTracking) {
      _stop();
      return;
    }
    if (state.running && state.plan.intervalSeconds == plan.intervalSeconds) {
      state = state.copyWith(plan: plan);
      return;
    }
    unawaited(_start(plan));
  }

  Future<void> _start(TrackingPlan plan) async {
    _cancel();
    final generation = ++_generation;
    final permission = await ref.read(locationServiceProvider).status();
    if (generation != _generation) return;
    if (permission != LocationPermissionState.granted) {
      state = TrackingStatus(plan: plan, permission: permission);
      return;
    }

    final l10n = lookupAppLocalizations(ref.read(localeProvider));
    final interval = Duration(seconds: plan.intervalSeconds);
    _subscription = ref
        .read(trackingSourceProvider)
        .track(
          interval: interval,
          notice: TrackingNotice(
            title: l10n.avNotificationTitle,
            text: l10n.avNotificationText,
          ),
        )
        .listen((point) => _latest = point, onError: (Object _) {});
    _timer = Timer.periodic(interval, (_) => unawaited(_flush()));
    state = TrackingStatus(
      running: true,
      plan: plan,
      lastUploadAt: state.lastUploadAt,
    );
  }

  Future<void> _flush() async {
    if (_flushing) return;
    _flushing = true;
    var latest = _latest;
    _latest = null;
    if (latest == null && _buffer.isEmpty) {
      // The platform stream sends nothing while the device stands still (iOS),
      // but the server takes a driver offline when positions stop: read a
      // fresh fix as the heartbeat.
      try {
        latest = await ref.read(locationServiceProvider).current();
      } on Object {
        latest = null;
      }
    }
    if (latest == null && _buffer.isEmpty) {
      _flushing = false;
      return;
    }
    if (latest != null) {
      _buffer.add(latest);
      if (_buffer.length > maxBuffered) _buffer.removeAt(0);
    }
    final batch = List<GeoPoint>.of(_buffer);
    try {
      final result = await ref
          .read(availabilityRepositoryProvider)
          .uploadLocations(batch);
      _buffer.removeWhere(batch.contains);
      if (_subscription == null) return;
      state = state.copyWith(
        lastUploadAt: DateTime.now(),
        lastUploadFailed: false,
      );
      if (result.tracking.state != state.plan.state ||
          result.tracking.intervalSeconds != state.plan.intervalSeconds) {
        // The server changed the tracking state (a job started, or the
        // provider went offline): re-read everything and follow it.
        unawaited(ref.read(availabilityProvider.notifier).refresh());
      }
    } on ApiException catch (e) {
      if (e.code == 'location_not_tracking') {
        // The server no longer considers this driver online.
        _buffer.clear();
        unawaited(ref.read(availabilityProvider.notifier).refresh());
      } else if (_subscription != null) {
        state = state.copyWith(lastUploadFailed: true);
      }
    } finally {
      _flushing = false;
    }
  }

  void _cancel() {
    _subscription?.cancel();
    _subscription = null;
    _timer?.cancel();
    _timer = null;
  }

  void _stop() {
    _generation++;
    _cancel();
    _latest = null;
    _buffer.clear();
    if (state.running || state.plan.isTracking) {
      state = const TrackingStatus();
    }
  }
}

final trackingControllerProvider =
    NotifierProvider<TrackingController, TrackingStatus>(
      TrackingController.new,
    );

/// Events delivered on the driver's private channel, for features that react
/// to them (the booking events of P2.4 listen to [stream]).
class RealtimeEventBus {
  final _controller = StreamController<RealtimeEvent>.broadcast();

  Stream<RealtimeEvent> get stream => _controller.stream;

  void add(RealtimeEvent event) {
    if (!_controller.isClosed) _controller.add(event);
  }

  void dispose() => _controller.close();
}

final providerRealtimeEventsProvider = Provider<RealtimeEventBus>((ref) {
  final bus = RealtimeEventBus();
  ref.onDispose(bus.dispose);
  return bus;
});

class RealtimeStatus {
  const RealtimeStatus({
    this.connection = RealtimeConnectionState.disconnected,
    this.channel,
  });

  final RealtimeConnectionState connection;

  /// The channel this device is subscribed to (without `private-`).
  final String? channel;
}

/// The realtime connection to `private-provider.{uuid}` with automatic
/// reconnect and a state the UI shows. Events are hints: availability events
/// and any gap in the `sequence` numbers re-read the snapshot, and so does every
/// (re)connect, because events may have been missed in between.
class RealtimeController extends Notifier<RealtimeStatus> {
  final _sequences = SequenceTracker();
  final _subscriptions = <StreamSubscription<Object?>>[];
  RealtimeClient? _client;
  String? _channel;
  bool _connecting = false;

  @override
  RealtimeStatus build() {
    ref.listen<AsyncValue<AvailabilitySnapshot>>(availabilityProvider, (
      _,
      next,
    ) {
      final channel = next.value?.availability.channelName;
      if (channel != null && channel != _channel) {
        unawaited(_connect(channel));
      }
    }, fireImmediately: true);
    ref.listen<SessionStatus>(sessionStatusProvider, (_, next) {
      if (next != SessionStatus.authenticated) unawaited(_disconnect());
    });
    ref.onDispose(() => unawaited(_teardown()));
    return const RealtimeStatus();
  }

  Future<void> _connect(String channel) async {
    if (_connecting) return;
    _connecting = true;
    try {
      await _disconnect();
      final client = ref.read(realtimeClientProvider);
      _client = client;
      _channel = channel;
      _sequences.reset();
      state = RealtimeStatus(
        connection: RealtimeConnectionState.connecting,
        channel: channel,
      );
      _subscriptions
        ..add(
          client.connectionState.listen(
            (s) => state = RealtimeStatus(connection: s, channel: _channel),
          ),
        )
        ..add(
          client.connected.listen((_) {
            _sequences.reset();
            unawaited(ref.read(availabilityProvider.notifier).refresh());
          }),
        );
      final events = await client.subscribePrivate(channel);
      _subscriptions.add(events.listen(_onEvent));
      await client.connect();
    } on Object {
      state = RealtimeStatus(
        connection: RealtimeConnectionState.disconnected,
        channel: _channel,
      );
    } finally {
      _connecting = false;
    }
  }

  void _onEvent(RealtimeEvent event) {
    // Pusher protocol housekeeping events are not ours.
    if (event.name.startsWith('pusher')) return;
    final sequence = event.sequence;
    final key = (event.data['booking_uuid'] as String?) ?? 'provider';
    final gap = sequence != null && _sequences.needsRefetch(key, sequence);
    if (event.name == 'provider.availability_changed' || gap) {
      unawaited(ref.read(availabilityProvider.notifier).refresh());
    }
    ref.read(providerRealtimeEventsProvider).add(event);
  }

  /// Stops listening and closes the connection. Touches no provider state, so
  /// it is safe while the provider is being disposed.
  Future<void> _teardown() async {
    final subscriptions = List.of(_subscriptions);
    _subscriptions.clear();
    for (final s in subscriptions) {
      await s.cancel();
    }
    final client = _client;
    final channel = _channel;
    _client = null;
    _channel = null;
    if (client != null && channel != null) {
      client.unsubscribe(channel);
      await client.disconnect();
    }
  }

  Future<void> _disconnect() async {
    final hadChannel = _channel != null;
    await _teardown();
    if (hadChannel && ref.mounted) state = const RealtimeStatus();
  }
}

final realtimeControllerProvider =
    NotifierProvider<RealtimeController, RealtimeStatus>(
      RealtimeController.new,
    );
