import 'dart:async';
import 'dart:convert';

import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../core/providers.dart';
import '../../../availability/presentation/providers/availability_providers.dart';
import '../../data/datasources/job_remote_data_source.dart';
import '../../data/repositories/job_repository_impl.dart';
import '../../domain/entities/job_entities.dart';
import '../../domain/repositories/job_repository.dart';

final jobRepositoryProvider = Provider<JobRepository>(
  (ref) => JobRepositoryImpl(JobRemoteDataSource(ref.watch(apiClientProvider))),
);

/// Failures show their retry state; no hidden automatic retries.
Duration? _noAutoRetry(int retryCount, Object error) => null;

/// Polling cadence when the socket is down, and when it is up (safety net).
const pollWhenOffline = Duration(seconds: 6);
const pollWhenLive = Duration(seconds: 25);

/// The realtime connection of the driver channel is up.
bool _isLive(Ref ref) =>
    ref.read(realtimeControllerProvider).connection ==
    RealtimeConnectionState.connected;

/// Wires a notifier to the driver channel: every booking event that [wants]
/// and every (re)connect calls [refresh] (events are hints, the server holds
/// the truth), and a timer polls as a safety net. Returns nothing; cleanup is
/// registered on [ref].
void _bindRealtime(
  Ref ref, {
  required bool Function(RealtimeEvent event) wants,
  required Future<void> Function() refresh,
  required DateTime Function() lastRefresh,
}) {
  final sub = ref.read(providerRealtimeEventsProvider).stream.listen((e) {
    if (wants(e)) unawaited(refresh());
  });
  ref.listen<RealtimeConnectionState>(
    realtimeControllerProvider.select((s) => s.connection),
    (previous, next) {
      if (next == RealtimeConnectionState.connected) unawaited(refresh());
    },
  );
  final timer = ref.read(pollingEnabledProvider)
      ? Timer.periodic(pollWhenOffline, (_) {
          final every = _isLive(ref) ? pollWhenLive : pollWhenOffline;
          if (DateTime.now().difference(lastRefresh()) >= every) {
            unawaited(refresh());
          }
        })
      : null;
  ref.onDispose(() {
    timer?.cancel();
    sub.cancel();
  });
}

bool _isBookingEvent(RealtimeEvent e) =>
    e.name.startsWith('booking.') || e.name == 'payment.status_changed';

String? _eventBooking(RealtimeEvent e) => e.data['booking_uuid'] as String?;

// ---------------------------------------------------------------------------
// Alert preferences (sound / vibration for new requests)

class RequestAlertPrefs {
  const RequestAlertPrefs({this.sound = true, this.vibration = true});

  final bool sound;
  final bool vibration;
}

const _soundKey = 'request_alert_sound';
const _vibrationKey = 'request_alert_vibration';

class RequestAlertPrefsNotifier extends Notifier<RequestAlertPrefs> {
  @override
  RequestAlertPrefs build() {
    final prefs = ref.watch(sharedPreferencesProvider);
    return RequestAlertPrefs(
      sound: prefs.getBool(_soundKey) ?? true,
      vibration: prefs.getBool(_vibrationKey) ?? true,
    );
  }

  Future<void> setSound(bool value) async {
    await ref.read(sharedPreferencesProvider).setBool(_soundKey, value);
    state = RequestAlertPrefs(sound: value, vibration: state.vibration);
  }

  Future<void> setVibration(bool value) async {
    await ref.read(sharedPreferencesProvider).setBool(_vibrationKey, value);
    state = RequestAlertPrefs(sound: state.sound, vibration: value);
  }
}

final requestAlertPrefsProvider =
    NotifierProvider<RequestAlertPrefsNotifier, RequestAlertPrefs>(
      RequestAlertPrefsNotifier.new,
    );

/// Plays the alert for a new request, per the driver's preferences.
final requestAlertProvider = Provider<void Function()>((ref) {
  return () {
    final prefs = ref.read(requestAlertPrefsProvider);
    if (prefs.sound) unawaited(SystemSound.play(SystemSoundType.alert));
    if (prefs.vibration) unawaited(HapticFeedback.heavyImpact());
  };
});

// ---------------------------------------------------------------------------
// Incoming requests

/// `GET /provider/requests/available`, refetched on every driver-channel
/// hint, on reconnect and by polling. A failed refresh keeps the last list.
class RequestsNotifier extends AsyncNotifier<RequestFeed> {
  bool _refreshing = false;
  DateTime _last = DateTime.now();
  final _seen = <String>{};
  bool _loaded = false;

  @override
  Future<RequestFeed> build() async {
    final repo = ref.watch(jobRepositoryProvider);
    _bindRealtime(
      ref,
      wants: (e) =>
          e.name == 'booking.requested' ||
          e.name == 'booking.offer_expired' ||
          e.name == 'booking.offer_updated' ||
          e.name == 'booking.provider_selected' ||
          e.name == 'booking.status_changed',
      refresh: refresh,
      lastRefresh: () => _last,
    );
    ref.listen<bool>(
      availabilityProvider.select((s) => s.value?.availability.online ?? false),
      (_, _) => unawaited(refresh()),
    );
    final feed = await repo.availableRequests();
    _remember(feed, alert: false);
    _last = DateTime.now();
    return feed;
  }

  void _remember(RequestFeed feed, {required bool alert}) {
    final fresh = [
      for (final r in feed.requests)
        if (!_seen.contains(r.id)) r.id,
    ];
    _seen
      ..clear()
      ..addAll(feed.requests.map((r) => r.id));
    if (alert && _loaded && fresh.isNotEmpty) ref.read(requestAlertProvider)();
    _loaded = true;
  }

  Future<void> refresh() async {
    if (_refreshing) return;
    _refreshing = true;
    try {
      final feed = await ref.read(jobRepositoryProvider).availableRequests();
      _remember(feed, alert: true);
      state = AsyncData(feed);
    } on ApiException catch (e, st) {
      if (state.value == null) state = AsyncError(e, st);
    } finally {
      _refreshing = false;
      _last = DateTime.now();
    }
  }

  /// Full reload with the loading state (retry button).
  Future<void> reload() async {
    state = const AsyncLoading<RequestFeed>();
    try {
      final feed = await ref.read(jobRepositoryProvider).availableRequests();
      _remember(feed, alert: false);
      state = AsyncData(feed);
    } on ApiException catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  /// Drops a request the driver skipped or lost, before the server answers.
  void drop(String bookingId) {
    final current = state.value;
    if (current == null) return;
    state = AsyncData(
      RequestFeed(
        requests: [
          for (final r in current.requests)
            if (r.id != bookingId) r,
        ],
        eligible: current.eligible,
        eligibility: current.eligibility,
      ),
    );
  }
}

final requestsProvider =
    AsyncNotifierProvider.autoDispose<RequestsNotifier, RequestFeed>(
      RequestsNotifier.new,
      retry: _noAutoRetry,
    );

// ---------------------------------------------------------------------------
// My offers

class MyOffersNotifier extends AsyncNotifier<List<MyOffer>> {
  bool _refreshing = false;
  DateTime _last = DateTime.now();

  @override
  Future<List<MyOffer>> build() async {
    final repo = ref.watch(jobRepositoryProvider);
    _bindRealtime(
      ref,
      wants: (e) =>
          e.name.startsWith('booking.offer_') ||
          e.name == 'booking.provider_selected' ||
          e.name == 'booking.status_changed',
      refresh: refresh,
      lastRefresh: () => _last,
    );
    final offers = await repo.myOffers();
    _last = DateTime.now();
    return offers;
  }

  Future<void> refresh() async {
    if (_refreshing) return;
    _refreshing = true;
    try {
      state = AsyncData(await ref.read(jobRepositoryProvider).myOffers());
    } on ApiException catch (e, st) {
      if (state.value == null) state = AsyncError(e, st);
    } finally {
      _refreshing = false;
      _last = DateTime.now();
    }
  }

  Future<void> reload() async {
    state = const AsyncLoading<List<MyOffer>>();
    try {
      state = AsyncData(await ref.read(jobRepositoryProvider).myOffers());
    } on ApiException catch (e, st) {
      state = AsyncError(e, st);
    }
  }
}

final myOffersProvider =
    AsyncNotifierProvider.autoDispose<MyOffersNotifier, List<MyOffer>>(
      MyOffersNotifier.new,
      retry: _noAutoRetry,
    );

/// The driver's open offers on [bookingId], used to show "offer sent".
MyOffer? pendingOfferFor(List<MyOffer>? offers, String bookingId) {
  if (offers == null) return null;
  for (final o in offers) {
    if (o.bookingId == bookingId && o.isPending) return o;
  }
  return null;
}

// ---------------------------------------------------------------------------
// Active job (restored from the server on start, resume and every hint)

/// `GET /provider/bookings/active`: the job the driver holds, or null. A
/// status event for a booking the driver no longer holds ends up here as
/// null, which drops the job from every screen.
class ActiveJobNotifier extends AsyncNotifier<Job?> {
  bool _refreshing = false;
  DateTime _last = DateTime.now();

  @override
  Future<Job?> build() async {
    final session = ref.watch(sessionStatusProvider);
    if (session != SessionStatus.authenticated) return null;
    final repo = ref.watch(jobRepositoryProvider);
    _bindRealtime(
      ref,
      wants: _isBookingEvent,
      refresh: refresh,
      lastRefresh: () => _last,
    );
    final job = await repo.activeJob();
    _last = DateTime.now();
    return job;
  }

  /// Re-reads the active job (app resume, events, reconnect, polling). A
  /// failed read keeps what is on screen.
  Future<void> refresh() async {
    if (_refreshing) return;
    _refreshing = true;
    try {
      final job = await ref.read(jobRepositoryProvider).activeJob();
      final previous = state.value;
      state = AsyncData(job);
      if (previous?.status != job?.status || previous?.id != job?.id) {
        // The tracking plan follows the job state (to pickup, active, idle).
        unawaited(ref.read(availabilityProvider.notifier).refresh());
      }
    } on ApiException catch (e, st) {
      if (state.value == null && !state.hasValue) state = AsyncError(e, st);
    } finally {
      _refreshing = false;
      _last = DateTime.now();
    }
  }

  void apply(Job? job) => state = AsyncData(job);

  Future<void> reload() async {
    state = const AsyncLoading<Job?>();
    try {
      state = AsyncData(await ref.read(jobRepositoryProvider).activeJob());
    } on ApiException catch (e, st) {
      state = AsyncError(e, st);
    }
  }
}

final activeJobProvider = AsyncNotifierProvider<ActiveJobNotifier, Job?>(
  ActiveJobNotifier.new,
  retry: _noAutoRetry,
);

// ---------------------------------------------------------------------------
// One job by id (job screen, history detail)

class JobTracker extends AsyncNotifier<Job> {
  JobTracker(this.bookingId);

  final String bookingId;
  bool _refreshing = false;
  DateTime _last = DateTime.now();

  @override
  Future<Job> build() async {
    final repo = ref.watch(jobRepositoryProvider);
    _bindRealtime(
      ref,
      wants: (e) => _isBookingEvent(e) && _eventBooking(e) == bookingId,
      refresh: refresh,
      lastRefresh: () => _last,
    );
    final job = await repo.job(bookingId);
    _last = DateTime.now();
    return job;
  }

  /// Refetches the snapshot. A 404 means the driver no longer holds the
  /// booking (cancelled and re-dispatched, reassigned): the screen drops it.
  Future<void> refresh() async {
    if (_refreshing) return;
    _refreshing = true;
    try {
      final job = await ref.read(jobRepositoryProvider).job(bookingId);
      state = AsyncData(job);
      ref.read(activeJobProvider.notifier).apply(job.isFinished ? null : job);
    } on ApiException catch (e, st) {
      if (e.kind == ApiFailureKind.notFound || state.value == null) {
        state = AsyncError(e, st);
      }
    } finally {
      _refreshing = false;
      _last = DateTime.now();
    }
  }

  void apply(Job job) {
    state = AsyncData(job);
    ref.read(activeJobProvider.notifier).apply(job.isFinished ? null : job);
  }

  Future<void> reload() async {
    state = const AsyncLoading<Job>();
    try {
      state = AsyncData(await ref.read(jobRepositoryProvider).job(bookingId));
    } on ApiException catch (e, st) {
      state = AsyncError(e, st);
    }
  }
}

final jobTrackerProvider = AsyncNotifierProvider.autoDispose
    .family<JobTracker, Job, String>(JobTracker.new, retry: _noAutoRetry);

/// A job read once (history rows); no realtime.
final jobDetailProvider = FutureProvider.autoDispose.family<Job, String>(
  (ref, id) => ref.watch(jobRepositoryProvider).job(id),
  retry: _noAutoRetry,
);

/// Job history (`GET /provider/bookings`), newest first, loaded page by page.
class JobHistoryState {
  const JobHistoryState({
    required this.items,
    required this.page,
    required this.hasMore,
    this.loadingMore = false,
  });

  final List<JobHistoryEntry> items;
  final int page;
  final bool hasMore;
  final bool loadingMore;
}

class JobHistoryNotifier extends AsyncNotifier<JobHistoryState> {
  @override
  Future<JobHistoryState> build() async {
    final page = await ref.read(jobRepositoryProvider).history();
    return JobHistoryState(
      items: page.items,
      page: page.currentPage,
      hasMore: page.hasMore,
    );
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(
      JobHistoryState(
        items: current.items,
        page: current.page,
        hasMore: true,
        loadingMore: true,
      ),
    );
    try {
      final next = await ref
          .read(jobRepositoryProvider)
          .history(page: current.page + 1);
      state = AsyncData(
        JobHistoryState(
          items: [...current.items, ...next.items],
          page: next.currentPage,
          hasMore: next.hasMore,
        ),
      );
    } on Object {
      state = AsyncData(
        JobHistoryState(
          items: current.items,
          page: current.page,
          hasMore: current.hasMore,
        ),
      );
    }
  }
}

final jobHistoryProvider =
    AsyncNotifierProvider.autoDispose<JobHistoryNotifier, JobHistoryState>(
      JobHistoryNotifier.new,
      retry: _noAutoRetry,
    );

// ---------------------------------------------------------------------------
// Writes

/// Keeps one Idempotency-Key per user intent: reused while the intent is
/// retried (a timeout, a repeated tap), dropped once the call succeeded or
/// failed for good.
class IntentKeys {
  final _keys = <String, String>{};

  String keyFor(String intent) => _keys.putIfAbsent(intent, newIdempotencyKey);

  bool has(String intent) => _keys.containsKey(intent);

  void clear(String intent) => _keys.remove(intent);
}

/// True when the same key must be sent again (the outcome is unknown or the
/// failure is transient).
bool keepsKey(ApiException e) =>
    e.isOffline ||
    e.kind == ApiFailureKind.server ||
    e.kind == ApiFailureKind.rateLimited ||
    e.code == 'idempotency_request_in_progress';

/// True for the answers that mean the offer or request is gone (expired, lost
/// to another driver, no longer open): the screen refreshes and says so.
bool isLostRequest(ApiException e) => const {
  'booking_already_assigned',
  'booking_not_open_for_offers',
  'booking_search_expired',
  'offer_expired',
  'offer_not_active',
  'offer_not_dispatched',
  'offer_provider_unavailable',
}.contains(e.code);

/// All driver writes. Duplicate taps share one in-flight call and one key.
class JobActions {
  JobActions(this._ref);

  final Ref _ref;
  final _intents = IntentKeys();
  final _pending = <String, Future<Object?>>{};
  final _evidence = <String, Map<String, Object?>>{};

  JobRepository get _repo => _ref.read(jobRepositoryProvider);

  Future<T> _run<T>(String intent, Future<T> Function(String key) call) {
    final inFlight = _pending[intent];
    if (inFlight != null) return inFlight.then((v) => v as T);
    final future = () async {
      final key = _intents.keyFor(intent);
      try {
        final result = await call(key);
        _intents.clear(intent);
        return result;
      } on ApiException catch (e) {
        if (!keepsKey(e)) _intents.clear(intent);
        rethrow;
      }
    }();
    _pending[intent] = future;
    return future.whenComplete(() => _pending.remove(intent));
  }

  void _refreshNegotiation() {
    _ref.invalidate(requestsProvider);
    _ref.invalidate(myOffersProvider);
  }

  Future<R> _negotiation<R>(Future<R> Function() call) async {
    try {
      return await call();
    } on ApiException catch (e) {
      if (isLostRequest(e) ||
          e.kind == ApiFailureKind.conflict ||
          e.kind == ApiFailureKind.validation) {
        _refreshNegotiation();
      }
      rethrow;
    }
  }

  Future<MyOffer> acceptCustomerFare(String bookingId) => _negotiation(
    () => _run('accept:$bookingId', (key) async {
      final offer = await _repo.acceptCustomerFare(
        bookingId,
        idempotencyKey: key,
      );
      _refreshNegotiation();
      return offer;
    }),
  );

  /// [scope] is the moving / freight scope (`OfferScope.toJson()`); it is part
  /// of the intent, so changing the scope is a new intent with a new key.
  Future<MyOffer> counterOffer(
    String bookingId,
    int amountMinor, {
    String? currency,
    Map<String, Object?>? scope,
  }) => _negotiation(
    () => _run(
      'counter:$bookingId:$amountMinor:${scope == null ? '' : jsonEncode(scope)}',
      (key) async {
        final offer = await _repo.counterOffer(
          bookingId,
          amountMinor: amountMinor,
          currency: currency,
          idempotencyKey: key,
          scope: scope,
        );
        _refreshNegotiation();
        return offer;
      },
    ),
  );

  Future<void> skip(String bookingId) => _negotiation(() async {
    await _repo.skip(bookingId);
    _ref.read(requestsProvider.notifier).drop(bookingId);
    _refreshNegotiation();
  });

  Future<void> withdraw(String offerId) => _negotiation(() async {
    await _repo.withdrawOffer(offerId);
    _refreshNegotiation();
  });

  Future<Job> _transition(
    String bookingId,
    String step,
    Future<Job> Function(String key) call, {
    String extra = '',
  }) => _run('$step:$bookingId$extra', (key) async {
    final job = await call(key);
    _ref.read(jobTrackerProvider(bookingId).notifier).apply(job);
    unawaited(_ref.read(availabilityProvider.notifier).refresh());
    return job;
  });

  Future<Job> acknowledge(String id) => _transition(
    id,
    'acknowledge',
    (key) => _repo.acknowledge(id, idempotencyKey: key),
  );

  /// Sends the arrival with the current GPS fix as evidence. A missing fix
  /// never blocks the arrival; the fix is read once per intent so a retry
  /// carries the same body as the first try.
  Future<Job> arrived(String id) async {
    final intent = 'arrived:$id';
    var evidence = _evidence[intent];
    if (evidence == null) {
      evidence = await _readEvidence();
      _evidence[intent] = evidence;
    }
    try {
      return await _transition(
        id,
        'arrived',
        (key) => _repo.arrived(id, idempotencyKey: key, evidence: evidence!),
      );
    } on ApiException catch (e) {
      if (!keepsKey(e)) _evidence.remove(intent);
      rethrow;
    } finally {
      if (!_intents.has(intent)) _evidence.remove(intent);
    }
  }

  Future<Map<String, Object?>> _readEvidence() async {
    try {
      final point = await _ref
          .read(locationServiceProvider)
          .current()
          .timeout(const Duration(seconds: 6));
      if (point == null) return const {};
      return {
        'lat': point.latitude,
        'lng': point.longitude,
        'accuracy_m': ?point.accuracyMeters,
        'recorded_at': (point.capturedAt ?? DateTime.now())
            .toUtc()
            .toIso8601String(),
      };
    } on Object {
      return const {};
    }
  }

  Future<Job> verifyPin(String id, String pin) =>
      _transition(
        id,
        'pin',
        (key) => _repo.verifyPin(id, pin, idempotencyKey: key),
        extra: ':$pin',
      ).catchError((Object e) {
        // A wrong PIN changes the booking (attempts left, lock): show it.
        if (e is ApiException && e.code.startsWith('trip_pin')) {
          unawaited(_ref.read(jobTrackerProvider(id).notifier).refresh());
        }
        throw e;
      });

  Future<Job> start(String id) =>
      _transition(id, 'start', (key) => _repo.start(id, idempotencyKey: key));

  Future<Job> complete(String id) => _transition(
    id,
    'complete',
    (key) => _repo.complete(id, idempotencyKey: key),
  );

  Future<CancellationPreview> previewCancel(
    String id,
    DriverCancelReason reason, {
    String? note,
  }) => _repo.previewCancel(id, reason, note: note);

  Future<CancelOutcome> cancel(
    String id,
    DriverCancelReason reason, {
    String? note,
  }) => _run('cancel:$id:${reason.value}', (key) async {
    final outcome = await _repo.cancel(
      id,
      reason,
      idempotencyKey: key,
      note: note,
    );
    final job = outcome.job;
    if (job != null) {
      _ref.read(jobTrackerProvider(id).notifier).apply(job);
    } else {
      // Re-dispatched: the driver no longer holds the booking.
      _ref.read(activeJobProvider.notifier).apply(null);
    }
    unawaited(_ref.read(availabilityProvider.notifier).refresh());
    _refreshNegotiation();
    return outcome;
  });

  Future<void> rateCustomer(
    String id, {
    required int stars,
    required List<CustomerRatingTag> tags,
    String? comment,
  }) => _run('rate:$id', (key) async {
    await _repo.rateCustomer(
      id,
      stars: stars,
      tags: tags,
      comment: comment,
      idempotencyKey: key,
    );
    unawaited(_ref.read(jobTrackerProvider(id).notifier).refresh());
  });

  Future<void> report(
    String id, {
    required DriverReportCategory category,
    required String description,
  }) => _run(
    'report:$id:${category.value}',
    (key) => _repo.report(
      id,
      category: category,
      description: description,
      idempotencyKey: key,
    ),
  );
}

final jobActionsProvider = Provider<JobActions>((ref) => JobActions(ref));

/// Options, bounds and vehicle class of the scope builder; read once per
/// opening of the builder.
final scopeTemplateProvider = FutureProvider.autoDispose<ScopeTemplate>(
  (ref) => ref.watch(jobRepositoryProvider).offerScope(),
  retry: _noAutoRetry,
);
