import 'dart:async';
import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../core/providers.dart';
import '../../../../core/realtime_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../payments/presentation/providers/payments_providers.dart';
import '../../data/datasources/booking_remote_data_source.dart';
import '../../data/repositories/booking_repository_impl.dart';
import '../../domain/entities/booking_entities.dart';
import '../../domain/repositories/booking_repository.dart';

final bookingRepositoryProvider = Provider<BookingRepository>(
  (ref) => BookingRepositoryImpl(
    BookingRemoteDataSource(ref.watch(apiClientProvider)),
  ),
);

/// Failures show their retry state; no hidden automatic retries.
Duration? _noAutoRetry(int retryCount, Object error) => null;

/// Polling cadence when the socket is down, and when it is up (safety net).
const pollWhenOffline = Duration(seconds: 5);
const pollWhenLive = Duration(seconds: 20);

bool _isBookingEvent(RealtimeEvent e) =>
    e.name.startsWith('booking.') || e.name == 'payment.status_changed';

String? _eventBooking(RealtimeEvent e) => e.data['booking_uuid'] as String?;

// ---------------------------------------------------------------------------
// Estimate

class EstimateArgs {
  EstimateArgs({
    required this.origin,
    required this.destination,
    required this.serviceCategoryId,
    Map<String, Object?> details = const {},
  }) : detailsKey = jsonEncode(details, toEncodable: (o) => '$o'),
       details = details;

  final LatLng origin;
  final LatLng destination;
  final int serviceCategoryId;
  final Map<String, Object?> details;
  final String detailsKey;

  @override
  bool operator ==(Object other) =>
      other is EstimateArgs &&
      other.origin == origin &&
      other.destination == destination &&
      other.serviceCategoryId == serviceCategoryId &&
      other.detailsKey == detailsKey;

  @override
  int get hashCode =>
      Object.hash(origin, destination, serviceCategoryId, detailsKey);
}

final routeEstimateProvider = FutureProvider.autoDispose
    .family<RouteEstimate, EstimateArgs>(
      (ref, args) => ref
          .watch(bookingRepositoryProvider)
          .estimate(
            origin: args.origin,
            destination: args.destination,
            serviceCategoryId: args.serviceCategoryId,
            serviceDetails: args.details,
          ),
      retry: _noAutoRetry,
    );

// ---------------------------------------------------------------------------
// Draft (the booking form)

class BookingDraft {
  const BookingDraft({
    this.fareMinor,
    this.passengers = 1,
    this.payment = PaymentMethodType.cash,
    this.submitting = false,
    this.error,
  });

  final int? fareMinor;
  final int passengers;
  final PaymentMethodType payment;
  final bool submitting;
  final ApiException? error;

  BookingDraft copyWith({
    int? fareMinor,
    int? passengers,
    PaymentMethodType? payment,
    bool? submitting,
    ApiException? error,
    bool clearError = false,
    bool clearFare = false,
  }) => BookingDraft(
    fareMinor: clearFare ? null : (fareMinor ?? this.fareMinor),
    passengers: passengers ?? this.passengers,
    payment: payment ?? this.payment,
    submitting: submitting ?? this.submitting,
    error: clearError ? null : (error ?? this.error),
  );
}

/// Booking form state. Input survives errors (nothing is cleared on failure).
/// One Idempotency-Key per user intent: reused while the payload is the same
/// (a retry after a timeout), renewed once the payload changes.
class BookingDraftNotifier extends Notifier<BookingDraft> {
  String? _key;
  String? _fingerprint;

  @override
  BookingDraft build() => const BookingDraft();

  void setFare(int? minor) {
    // A different fare makes a coupon quote stale (the code stays; the server prices the trip).
    ref.read(couponCheckProvider.notifier).invalidateQuote();
    state = minor == null
        ? state.copyWith(clearFare: true, clearError: true)
        : state.copyWith(fareMinor: minor, clearError: true);
  }

  void setPassengers(int count) =>
      state = state.copyWith(passengers: count, clearError: true);

  void setPayment(PaymentMethodType method) {
    ref.read(couponCheckProvider.notifier).invalidateQuote();
    state = state.copyWith(payment: method, clearError: true);
  }

  void reset() {
    ref.read(couponCheckProvider.notifier).clear();
    _key = null;
    _fingerprint = null;
    state = const BookingDraft();
  }

  /// Creates the booking. Returns it, or null after storing [BookingDraft.error].
  Future<Booking?> submit(BookingRequest request) async {
    if (state.submitting) return null;
    final fingerprint = jsonEncode([
      request.serviceCategoryId,
      request.pickup.lat,
      request.pickup.lng,
      request.dropoff?.lat,
      request.dropoff?.lng,
      request.proposedFareMinor,
      request.passengers,
      request.paymentMethod.value,
      request.couponCode,
      request.bookingType.value,
      request.restrictedGoodsPolicyVersion,
      [
        for (final s in request.stops)
          [
            s.place.lat,
            s.place.lng,
            s.contactName,
            s.contactPhone,
            s.contactNote,
          ],
      ],
      jsonEncode(request.serviceDetails, toEncodable: (o) => '$o'),
    ]);
    if (fingerprint != _fingerprint || _key == null) {
      _fingerprint = fingerprint;
      _key = newIdempotencyKey();
    }
    state = state.copyWith(submitting: true, clearError: true);
    try {
      final booking = await ref
          .read(bookingRepositoryProvider)
          .create(request, idempotencyKey: _key!);
      _key = null;
      _fingerprint = null;
      state = const BookingDraft();
      ref.read(couponCheckProvider.notifier).clear();
      ref.invalidate(activeBookingProvider);
      return booking;
    } on ApiException catch (e) {
      state = state.copyWith(submitting: false, error: e);
      return null;
    }
  }
}

final bookingDraftProvider =
    NotifierProvider<BookingDraftNotifier, BookingDraft>(
      BookingDraftNotifier.new,
    );

// ---------------------------------------------------------------------------
// Tracked booking (snapshot + realtime + polling fallback)

class ProviderFix {
  const ProviderFix({
    required this.point,
    required this.recordedAt,
    required this.receivedAt,
    this.heading,
  });

  final LatLng point;
  final DateTime recordedAt;
  final DateTime receivedAt;
  final double? heading;
}

class TrackedBooking {
  const TrackedBooking({required this.booking, this.fix});

  final Booking booking;
  final ProviderFix? fix;
}

/// One booking kept current: snapshot fetched from the API, refetched on every
/// realtime hint, on reconnect and by a polling fallback. Location events
/// update the provider marker directly.
class BookingTracker extends AsyncNotifier<TrackedBooking> {
  BookingTracker(this.bookingId);

  final String bookingId;
  final _sequences = SequenceTracker();
  bool _refreshing = false;
  DateTime _lastRefresh = DateTime.now();

  @override
  Future<TrackedBooking> build() async {
    final repo = ref.watch(bookingRepositoryProvider);
    final hub = ref.watch(customerEventsProvider);

    final subs = <StreamSubscription<Object?>>[
      hub.events.listen(_onEvent),
      hub.reconnected.listen((_) => refresh()),
    ];
    final timer = ref.read(pollingEnabledProvider)
        ? Timer.periodic(pollWhenOffline, (_) {
            final every = hub.isLive ? pollWhenLive : pollWhenOffline;
            if (DateTime.now().difference(_lastRefresh) >= every) refresh();
          })
        : null;
    ref.onDispose(() {
      timer?.cancel();
      for (final s in subs) {
        s.cancel();
      }
    });

    final booking = await repo.get(bookingId);
    _sequences.resync(bookingId, booking.sequence);
    _lastRefresh = DateTime.now();
    return TrackedBooking(booking: booking);
  }

  void _onEvent(RealtimeEvent e) {
    if (!_isBookingEvent(e) || _eventBooking(e) != bookingId) return;
    final sequence = e.sequence;
    if (e.name == 'booking.location_updated') {
      final payload = e.data['payload'];
      if (payload is Map) {
        final lat = (payload['lat'] as num?)?.toDouble();
        final lng = (payload['lng'] as num?)?.toDouble();
        final current = state.value;
        if (lat != null && lng != null && current != null) {
          final now = DateTime.now();
          state = AsyncData(
            TrackedBooking(
              booking: current.booking,
              fix: ProviderFix(
                point: LatLng(lat, lng),
                recordedAt:
                    DateTime.tryParse('${payload['recorded_at']}') ?? now,
                receivedAt: now,
                heading: (payload['heading'] as num?)?.toDouble(),
              ),
            ),
          );
        }
      }
      if (sequence != null) _sequences.resync(bookingId, sequence);
      return;
    }
    // Any other event is a hint: refetch the authoritative snapshot, also
    // when the sequence shows a gap.
    if (sequence != null) _sequences.needsRefetch(bookingId, sequence);
    refresh();
  }

  /// Refetches the snapshot, keeping the last good one when the call fails
  /// (the offline banner and polling cover that).
  Future<void> refresh() async {
    if (_refreshing) return;
    _refreshing = true;
    try {
      final booking = await ref.read(bookingRepositoryProvider).get(bookingId);
      apply(booking);
    } on ApiException catch (e, st) {
      if (state.value == null) state = AsyncError(e, st);
    } finally {
      _refreshing = false;
      _lastRefresh = DateTime.now();
    }
  }

  /// Shows [booking] (a response to one of our own writes) at once.
  void apply(Booking booking) {
    _sequences.resync(bookingId, booking.sequence);
    final previous = state.value;
    state = AsyncData(
      TrackedBooking(
        booking: booking,
        fix: booking.trip.providerLocationVisible ? previous?.fix : null,
      ),
    );
  }

  /// Full reload with the loading state (pull to refresh / retry button).
  Future<void> reload() async {
    state = const AsyncLoading<TrackedBooking>();
    try {
      final booking = await ref.read(bookingRepositoryProvider).get(bookingId);
      _sequences.resync(bookingId, booking.sequence);
      state = AsyncData(TrackedBooking(booking: booking));
    } on ApiException catch (e, st) {
      state = AsyncError(e, st);
    }
  }
}

final bookingTrackerProvider = AsyncNotifierProvider.autoDispose
    .family<BookingTracker, TrackedBooking, String>(
      BookingTracker.new,
      retry: _noAutoRetry,
    );

// ---------------------------------------------------------------------------
// Offers of one booking

class OffersNotifier extends AsyncNotifier<List<Offer>> {
  OffersNotifier(this.bookingId);

  final String bookingId;
  bool _refreshing = false;
  DateTime _lastRefresh = DateTime.now();

  @override
  Future<List<Offer>> build() async {
    final repo = ref.watch(bookingRepositoryProvider);
    final hub = ref.watch(customerEventsProvider);
    final subs = <StreamSubscription<Object?>>[
      hub.events.listen((e) {
        if (_eventBooking(e) == bookingId &&
            (e.name.startsWith('booking.offer_') ||
                e.name == 'booking.provider_selected' ||
                e.name == 'booking.status_changed' ||
                e.name == 'booking.request_changed')) {
          refresh();
        }
      }),
      hub.reconnected.listen((_) => refresh()),
    ];
    final timer = ref.read(pollingEnabledProvider)
        ? Timer.periodic(pollWhenOffline, (_) {
            final every = hub.isLive ? pollWhenLive : pollWhenOffline;
            if (DateTime.now().difference(_lastRefresh) >= every) refresh();
          })
        : null;
    ref.onDispose(() {
      timer?.cancel();
      for (final s in subs) {
        s.cancel();
      }
    });
    final offers = await repo.offers(bookingId);
    _lastRefresh = DateTime.now();
    return offers;
  }

  Future<void> refresh() async {
    if (_refreshing) return;
    _refreshing = true;
    try {
      state = AsyncData(
        await ref.read(bookingRepositoryProvider).offers(bookingId),
      );
    } on ApiException catch (e, st) {
      if (state.value == null) state = AsyncError(e, st);
    } finally {
      _refreshing = false;
      _lastRefresh = DateTime.now();
    }
  }
}

final offersProvider = AsyncNotifierProvider.autoDispose
    .family<OffersNotifier, List<Offer>, String>(
      OffersNotifier.new,
      retry: _noAutoRetry,
    );

class OfferSortNotifier extends Notifier<OfferSort> {
  @override
  OfferSort build() => OfferSort.price;

  void select(OfferSort sort) => state = sort;
}

final offerSortProvider = NotifierProvider<OfferSortNotifier, OfferSort>(
  OfferSortNotifier.new,
);

/// Offers ordered for display; missing values go last.
List<Offer> sortOffers(List<Offer> offers, OfferSort sort) {
  int cmpNullable(num? a, num? b) {
    if (a == null && b == null) return 0;
    if (a == null) return 1;
    if (b == null) return -1;
    return a.compareTo(b);
  }

  final sorted = [...offers];
  sorted.sort(switch (sort) {
    OfferSort.price => (a, b) => cmpNullable(a.amount?.minor, b.amount?.minor),
    OfferSort.eta => (a, b) => cmpNullable(a.etaSeconds, b.etaSeconds),
    OfferSort.rating => (a, b) => cmpNullable(
      b.provider?.ratingAverage,
      a.provider?.ratingAverage,
    ),
  });
  return sorted;
}

// ---------------------------------------------------------------------------
// Active booking (Home banner)

class ActiveBookingNotifier extends AsyncNotifier<BookingSummary?> {
  bool _refreshing = false;

  @override
  Future<BookingSummary?> build() async {
    final customerId = ref.watch(meProvider.select((m) => m.value?.id));
    if (customerId == null) return null;
    final hub = ref.watch(customerEventsProvider);
    final subs = <StreamSubscription<Object?>>[
      hub.events.listen((e) {
        if (_isBookingEvent(e) && e.name != 'booking.location_updated') {
          refresh();
        }
      }),
      hub.reconnected.listen((_) => refresh()),
    ];
    final timer = ref.read(pollingEnabledProvider)
        ? Timer.periodic(const Duration(seconds: 30), (_) => refresh())
        : null;
    ref.onDispose(() {
      timer?.cancel();
      for (final s in subs) {
        s.cancel();
      }
    });
    return ref.read(bookingRepositoryProvider).active();
  }

  Future<void> refresh() async {
    if (_refreshing) return;
    _refreshing = true;
    try {
      state = AsyncData(await ref.read(bookingRepositoryProvider).active());
    } on ApiException {
      // Keep the last known value; the next tick tries again.
    } finally {
      _refreshing = false;
    }
  }
}

final activeBookingProvider =
    AsyncNotifierProvider<ActiveBookingNotifier, BookingSummary?>(
      ActiveBookingNotifier.new,
      retry: _noAutoRetry,
    );

// ---------------------------------------------------------------------------
// Actions with per-intent idempotency keys

/// Codes after which an accept can never succeed: refresh and tell the user.
const acceptLostCodes = {
  'booking_already_assigned',
  'offer_expired',
  'offer_not_active',
  'offer_provider_unavailable',
  'booking_not_open_for_offers',
  'booking_search_expired',
};

class BookingActions {
  BookingActions(this._ref);

  final Ref _ref;
  final _keys = <String, String>{};

  BookingRepository get _repo => _ref.read(bookingRepositoryProvider);

  /// One key per user intent, kept across retries until the intent ends.
  String _key(String intent) => _keys.putIfAbsent(intent, newIdempotencyKey);

  BookingTracker _tracker(String id) =>
      _ref.read(bookingTrackerProvider(id).notifier);

  void _syncAfterWrite(String id) {
    _ref.invalidate(offersProvider(id));
    _ref.invalidate(activeBookingProvider);
  }

  /// Accept: a 409/422 that cannot succeed later refreshes everything, then
  /// rethrows so the screen can show the specific message.
  Future<Booking> acceptOffer(String bookingId, String offerId) async {
    final intent = 'accept:$bookingId:$offerId';
    try {
      final booking = await _repo.acceptOffer(
        bookingId,
        offerId,
        idempotencyKey: _key(intent),
      );
      _keys.remove(intent);
      _tracker(bookingId).apply(booking);
      _syncAfterWrite(bookingId);
      return booking;
    } on ApiException catch (e) {
      if (acceptLostCodes.contains(e.code) ||
          e.kind == ApiFailureKind.validation) {
        _keys.remove(intent);
        _tracker(bookingId).refresh();
        _ref.invalidate(offersProvider(bookingId));
      }
      rethrow;
    }
  }

  Future<void> rejectOffer(String bookingId, String offerId) async {
    await _repo.rejectOffer(bookingId, offerId);
    _ref.invalidate(offersProvider(bookingId));
    _tracker(bookingId).refresh();
  }

  Future<Booking> changeFare(String bookingId, int fareMinor) async {
    final intent = 'fare:$bookingId:$fareMinor';
    try {
      final booking = await _repo.updateRequest(
        bookingId,
        idempotencyKey: _key(intent),
        proposedFareMinor: fareMinor,
      );
      _keys.remove(intent);
      _tracker(bookingId).apply(booking);
      _syncAfterWrite(bookingId);
      return booking;
    } on ApiException catch (e) {
      if (e.kind == ApiFailureKind.conflict ||
          e.kind == ApiFailureKind.validation) {
        _keys.remove(intent);
        _tracker(bookingId).refresh();
      }
      rethrow;
    }
  }

  Future<CancellationPreview> previewCancel(
    String bookingId,
    CancelReason reason,
    String? note,
  ) => _repo.previewCancel(bookingId, reason: reason, note: note);

  Future<Booking> cancel(
    String bookingId, {
    required CancelReason reason,
    required int acceptedFeeMinor,
    String? note,
  }) async {
    final intent = 'cancel:$bookingId:${reason.value}:$acceptedFeeMinor';
    try {
      final booking = await _repo.cancel(
        bookingId,
        reason: reason,
        note: note,
        acceptedFeeMinor: acceptedFeeMinor > 0 ? acceptedFeeMinor : null,
        idempotencyKey: _key(intent),
      );
      _keys.remove(intent);
      _tracker(bookingId).apply(booking);
      _syncAfterWrite(bookingId);
      return booking;
    } on ApiException catch (e) {
      if (e.kind == ApiFailureKind.conflict ||
          e.kind == ApiFailureKind.validation) {
        _keys.remove(intent);
      }
      rethrow;
    }
  }

  Future<ShareLink> startShare(String bookingId) async {
    final intent = 'share:$bookingId';
    final link = await _repo.startShare(
      bookingId,
      idempotencyKey: _key(intent),
    );
    _keys.remove(intent);
    return link;
  }

  Future<void> stopShare(String bookingId) async {
    final intent = 'unshare:$bookingId';
    await _repo.stopShare(bookingId, idempotencyKey: _key(intent));
    _keys.remove(intent);
  }

  Future<void> rate(
    String bookingId, {
    required int stars,
    required List<String> tags,
    String? comment,
  }) async {
    final intent = 'rate:$bookingId';
    try {
      await _repo.rate(
        bookingId,
        stars: stars,
        tags: tags,
        comment: comment,
        idempotencyKey: _key(intent),
      );
      _keys.remove(intent);
    } on ApiException catch (e) {
      if (e.kind != ApiFailureKind.network &&
          e.kind != ApiFailureKind.timeout) {
        _keys.remove(intent);
      }
      rethrow;
    } finally {
      _tracker(bookingId).refresh();
    }
  }

  Future<void> report(
    String bookingId, {
    required ReportCategory category,
    required String description,
  }) async {
    final intent = 'report:$bookingId:${category.value}';
    try {
      await _repo.report(
        bookingId,
        category: category,
        description: description,
        idempotencyKey: _key(intent),
      );
      _keys.remove(intent);
    } on ApiException catch (e) {
      if (e.kind != ApiFailureKind.network &&
          e.kind != ApiFailureKind.timeout) {
        _keys.remove(intent);
      }
      rethrow;
    }
  }
}

final bookingActionsProvider = Provider<BookingActions>(BookingActions.new);
