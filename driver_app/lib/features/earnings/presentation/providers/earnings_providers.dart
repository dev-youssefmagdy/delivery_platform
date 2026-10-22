import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/providers.dart';
import '../../../availability/presentation/providers/availability_providers.dart';
import '../../../jobs/presentation/providers/job_providers.dart';

Duration? _noAutoRetry(int retryCount, Object error) => null;

final earningsRepositoryProvider = Provider<EarningsRepository>((ref) => ApiEarningsRepository(ref.watch(apiClientProvider)));

final adjustmentsRepositoryProvider = Provider<AdjustmentsRepository>(
  (ref) => ApiAdjustmentsRepository(ref.watch(apiClientProvider), bookingPrefix: '/provider/bookings'),
);

// ---------------------------------------------------------------------------
// Error texts

/// Maps a failure of the earnings, payout and cash calls to ARB text. The stable
/// code decides; the server message is the last fallback.
String moneyErrorText(AppLocalizations l10n, Object error, {String Function(String)? formatMinor}) {
  if (error is! ApiException) return l10n.errorGenericBody;
  if (error.isOffline) return l10n.errorOfflineBody;
  if (error.isSessionExpired) return l10n.errorSessionExpiredBody;
  switch (error.code) {
    case 'payout_amount_below_minimum':
      return l10n.poErrBelowMin;
    case 'payout_amount_above_maximum':
      return l10n.poErrAboveMax;
    case 'payout_insufficient_balance':
      return l10n.poErrInsufficient;
    case 'payout_method_not_verified':
      return l10n.poErrNotVerified;
    case 'payout_provider_not_approved':
      return l10n.poErrNotApproved;
    case 'payout_method_in_use':
      return l10n.poErrMethodInUse;
    case 'payout_not_cancellable':
      return l10n.poErrNotCancellable;
    case 'idempotency_key_reused':
      return l10n.poErrKeyReused;
    case 'cash_not_collectable':
      return l10n.cashErrNotCollectable;
    case 'adjustment_not_allowed_now':
      return l10n.dadjErrNotNow;
    case 'adjustment_open_limit':
      return l10n.dadjErrOpenLimit;
    case 'adjustment_amount_invalid':
      return l10n.dadjErrAmount;
    case 'adjustment_evidence_required':
      return l10n.dadjErrEvidence;
    case 'adjustment_type_not_allowed':
      return l10n.dadjErrType;
    case 'adjustment_not_pending':
    case 'adjustment_expired':
      return l10n.dadjErrNotPending;
    case 'adjustment_needs_counterpart':
      return l10n.dadjErrCounterpart;
    case 'receipt_not_available':
      return l10n.rcpErrNotAvailable;
    case 'idempotency_request_in_progress':
      return l10n.jbErrInProgress;
    case 'too_many_requests':
      return l10n.otpErrorTooManyRequests(error.retryAfterSeconds ?? 60);
  }
  if (error.kind == ApiFailureKind.validation) {
    for (final messages in error.fieldErrors.values) {
      if (messages.isNotEmpty) return messages.first;
    }
  }
  return error.message ?? l10n.errorGenericBody;
}

// ---------------------------------------------------------------------------
// Earnings dashboard

class EarningsPeriodNotifier extends Notifier<EarningsPeriod> {
  @override
  EarningsPeriod build() => EarningsPeriod.day;

  void select(EarningsPeriod period) => state = period;
}

final earningsPeriodProvider = NotifierProvider<EarningsPeriodNotifier, EarningsPeriod>(EarningsPeriodNotifier.new);

bool _moneyHint(RealtimeEvent e) => e.name.startsWith('booking.') || e.name == 'payment.status_changed';

/// Refetches when a trip finishes or a payment changes (events are hints) and on reconnect.
void _refetchOnHints(Ref ref, void Function() invalidate) {
  final sub = ref.read(providerRealtimeEventsProvider).stream.listen((e) {
    if (_moneyHint(e)) invalidate();
  });
  ref.listen<RealtimeConnectionState>(
    realtimeControllerProvider.select((s) => s.connection),
    (previous, next) {
      if (next == RealtimeConnectionState.connected) invalidate();
    },
  );
  ref.onDispose(sub.cancel);
}

final earningsSummaryProvider = FutureProvider.autoDispose<EarningsSummary>((ref) {
  ref.watch(sessionStatusProvider);
  final period = ref.watch(earningsPeriodProvider);
  _refetchOnHints(ref, ref.invalidateSelf);
  return ref.read(earningsRepositoryProvider).summary(period);
}, retry: _noAutoRetry);

/// The wallet position alone (cash debt banner on the Home and Earnings tabs).
final walletPositionProvider = FutureProvider.autoDispose<ProviderWalletPosition>((ref) async {
  ref.watch(sessionStatusProvider);
  _refetchOnHints(ref, ref.invalidateSelf);
  final summary = await ref.read(earningsRepositoryProvider).summary(EarningsPeriod.day);
  return summary.wallet;
}, retry: _noAutoRetry);

// ---------------------------------------------------------------------------
// Paged lists: booking-level earnings, wallet ledger

class PagedState<T> {
  const PagedState({required this.items, required this.page, required this.hasMore, this.loadingMore = false});

  final List<T> items;
  final int page;
  final bool hasMore;
  final bool loadingMore;

  PagedState<T> copyWith({List<T>? items, int? page, bool? hasMore, bool? loadingMore}) => PagedState(
    items: items ?? this.items,
    page: page ?? this.page,
    hasMore: hasMore ?? this.hasMore,
    loadingMore: loadingMore ?? this.loadingMore,
  );
}

abstract class PagedNotifier<T> extends AsyncNotifier<PagedState<T>> {
  Future<PagedList<T>> fetch(int page);

  @override
  Future<PagedState<T>> build() async {
    ref.watch(sessionStatusProvider);
    final first = await fetch(1);
    return PagedState(items: first.items, page: first.currentPage, hasMore: first.hasMore);
  }

  Future<void> reload() async {
    state = AsyncLoading<PagedState<T>>();
    try {
      final first = await fetch(1);
      state = AsyncData(PagedState(items: first.items, page: first.currentPage, hasMore: first.hasMore));
    } on Object catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  Future<void> refresh() async {
    try {
      final first = await fetch(1);
      state = AsyncData(PagedState(items: first.items, page: first.currentPage, hasMore: first.hasMore));
    } on Object {
      // Keep the list on screen.
    }
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(current.copyWith(loadingMore: true));
    try {
      final next = await fetch(current.page + 1);
      state = AsyncData(current.copyWith(items: [...current.items, ...next.items], page: next.currentPage, hasMore: next.hasMore, loadingMore: false));
    } on Object {
      state = AsyncData(current.copyWith(loadingMore: false));
    }
  }
}

class EarningLinesNotifier extends PagedNotifier<EarningLine> {
  @override
  Future<PagedList<EarningLine>> fetch(int page) => ref.read(earningsRepositoryProvider).transactions(page: page);
}

final earningLinesProvider = AsyncNotifierProvider.autoDispose<EarningLinesNotifier, PagedState<EarningLine>>(
  EarningLinesNotifier.new,
  retry: _noAutoRetry,
);

class WalletLinesNotifier extends PagedNotifier<WalletLine> {
  @override
  Future<PagedList<WalletLine>> fetch(int page) => ref.read(earningsRepositoryProvider).walletLines(page: page);
}

final walletLinesProvider = AsyncNotifierProvider.autoDispose<WalletLinesNotifier, PagedState<WalletLine>>(
  WalletLinesNotifier.new,
  retry: _noAutoRetry,
);

// ---------------------------------------------------------------------------
// Payout methods

class PayoutMethodsNotifier extends AsyncNotifier<List<PayoutMethod>> {
  final _keys = <String, String>{};

  @override
  Future<List<PayoutMethod>> build() {
    ref.watch(sessionStatusProvider);
    return ref.read(earningsRepositoryProvider).payoutMethods();
  }

  Future<void> reload() async {
    state = const AsyncLoading<List<PayoutMethod>>();
    try {
      state = AsyncData(await ref.read(earningsRepositoryProvider).payoutMethods());
    } on Object catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  Future<void> refresh() async {
    try {
      state = AsyncData(await ref.read(earningsRepositoryProvider).payoutMethods());
    } on Object {
      // Keep the list.
    }
  }

  /// One key per form intent: [intentId] is created by the form once and reused on retry.
  Future<void> save(NewPayoutMethodInput input, {required String intentId, String? editingId}) async {
    final key = _keys.putIfAbsent(intentId, newIdempotencyKey);
    final repo = ref.read(earningsRepositoryProvider);
    if (editingId == null) {
      await repo.addPayoutMethod(input, key: key);
    } else {
      await repo.updatePayoutMethod(editingId, input, key: key);
    }
    _keys.remove(intentId);
    await refresh();
  }

  Future<void> makeDefault(String id) async {
    final key = _keys.putIfAbsent('default:$id', newIdempotencyKey);
    await ref.read(earningsRepositoryProvider).makeDefault(id, key: key);
    _keys.remove('default:$id');
    await refresh();
  }

  Future<void> delete(String id) async {
    final key = _keys.putIfAbsent('delete:$id', newIdempotencyKey);
    await ref.read(earningsRepositoryProvider).deletePayoutMethod(id, key: key);
    _keys.remove('delete:$id');
    await refresh();
  }
}

final payoutMethodsProvider = AsyncNotifierProvider<PayoutMethodsNotifier, List<PayoutMethod>>(PayoutMethodsNotifier.new, retry: _noAutoRetry);

// ---------------------------------------------------------------------------
// Payouts

class PayoutsState {
  const PayoutsState({required this.items, required this.page, required this.hasMore, required this.balance, this.loadingMore = false});

  final List<PayoutRequest> items;
  final int page;
  final bool hasMore;
  final PayoutBalance? balance;
  final bool loadingMore;
}

class PayoutsNotifier extends AsyncNotifier<PayoutsState> {
  String? _requestKey;
  String? _requestFingerprint;
  final _cancelKeys = <String, String>{};

  @override
  Future<PayoutsState> build() async {
    ref.watch(sessionStatusProvider);
    // Payout status changes arrive as push notifications; the list also refreshes on reconnect.
    ref.listen<RealtimeConnectionState>(
      realtimeControllerProvider.select((s) => s.connection),
      (previous, next) {
        if (next == RealtimeConnectionState.connected) unawaited(refresh());
      },
    );
    return _load();
  }

  Future<PayoutsState> _load() async {
    final list = await ref.read(earningsRepositoryProvider).payouts();
    return PayoutsState(items: list.page.items, page: list.page.currentPage, hasMore: list.page.hasMore, balance: list.balance);
  }

  Future<void> reload() async {
    state = const AsyncLoading<PayoutsState>();
    try {
      state = AsyncData(await _load());
    } on Object catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  Future<void> refresh() async {
    try {
      state = AsyncData(await _load());
    } on Object {
      // Keep what is shown.
    }
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(PayoutsState(items: current.items, page: current.page, hasMore: current.hasMore, balance: current.balance, loadingMore: true));
    try {
      final next = await ref.read(earningsRepositoryProvider).payouts(page: current.page + 1);
      state = AsyncData(
        PayoutsState(items: [...current.items, ...next.page.items], page: next.page.currentPage, hasMore: next.page.hasMore, balance: next.balance ?? current.balance),
      );
    } on Object {
      state = AsyncData(PayoutsState(items: current.items, page: current.page, hasMore: current.hasMore, balance: current.balance));
    }
  }

  /// Requests a payout. The Idempotency-Key belongs to the (amount, method)
  /// intent and survives retries after a network drop; a different amount is a new intent.
  Future<PayoutRequest> request({required int amountMinor, required String methodId}) async {
    final fingerprint = '$amountMinor|$methodId';
    if (_requestKey == null || _requestFingerprint != fingerprint) {
      _requestKey = newIdempotencyKey();
      _requestFingerprint = fingerprint;
    }
    final payout = await ref.read(earningsRepositoryProvider).requestPayout(amountMinor: amountMinor, methodId: methodId, key: _requestKey!);
    _requestKey = null;
    _requestFingerprint = null;
    await refresh();
    ref.invalidate(walletPositionProvider);
    ref.invalidate(earningsSummaryProvider);
    return payout;
  }

  Future<PayoutRequest> cancel(String id) async {
    final key = _cancelKeys.putIfAbsent(id, newIdempotencyKey);
    final payout = await ref.read(earningsRepositoryProvider).cancelPayout(id, key: key);
    _cancelKeys.remove(id);
    ref.invalidate(payoutDetailProvider(id));
    await refresh();
    ref.invalidate(walletPositionProvider);
    ref.invalidate(earningsSummaryProvider);
    return payout;
  }
}

final payoutsProvider = AsyncNotifierProvider<PayoutsNotifier, PayoutsState>(PayoutsNotifier.new, retry: _noAutoRetry);

final payoutDetailProvider = FutureProvider.autoDispose.family<PayoutRequest, String>((ref, id) {
  ref.watch(sessionStatusProvider);
  return ref.read(earningsRepositoryProvider).payout(id);
}, retry: _noAutoRetry);

// ---------------------------------------------------------------------------
// Cash collected

class CashActions {
  CashActions(this._ref);

  final Ref _ref;
  final _keys = <String, String>{};

  /// Confirms the cash of a completed cash booking. The key is kept per booking
  /// so a retry after a dropped connection is a replay, not a second confirmation.
  Future<PaymentInfo> confirm(String bookingId) async {
    final key = _keys.putIfAbsent(bookingId, newIdempotencyKey);
    final payment = await _ref.read(earningsRepositoryProvider).confirmCash(bookingId, key: key);
    _keys.remove(bookingId);
    unawaited(_ref.read(jobTrackerProvider(bookingId).notifier).refresh());
    _ref.invalidate(walletPositionProvider);
    _ref.invalidate(earningsSummaryProvider);
    return payment;
  }
}

final cashActionsProvider = Provider<CashActions>(CashActions.new);

// ---------------------------------------------------------------------------
// Adjustments of one job

class JobAdjustmentsNotifier extends AsyncNotifier<List<Adjustment>> {
  JobAdjustmentsNotifier(this.bookingId);

  final String bookingId;
  final _keys = <String, String>{};
  String? _createKey;
  String? _createFingerprint;

  @override
  Future<List<Adjustment>> build() async {
    ref.watch(sessionStatusProvider);
    final sub = ref.watch(providerRealtimeEventsProvider).stream.listen((e) {
      if (e.name == 'booking.adjustment_changed' && e.data['booking_uuid'] == bookingId) unawaited(refresh());
    });
    final timer = Timer.periodic(const Duration(seconds: 30), (_) => unawaited(refresh()));
    ref.onDispose(() {
      timer.cancel();
      sub.cancel();
    });
    return ref.read(adjustmentsRepositoryProvider).list(bookingId);
  }

  Future<void> reload() async {
    ref.invalidateSelf();
    await future;
  }

  Future<void> refresh() async {
    try {
      state = AsyncData(await ref.read(adjustmentsRepositoryProvider).list(bookingId));
    } on Object {
      // Keep what is shown.
    }
  }

  /// Creates a request. One key per distinct request; a retry of the same one replays.
  Future<Adjustment> create(NewAdjustmentInput input) async {
    final fingerprint = '${input.type.value}|${input.amountMinor}|${input.reason}|${input.note}';
    if (_createKey == null || _createFingerprint != fingerprint) {
      _createKey = newIdempotencyKey();
      _createFingerprint = fingerprint;
    }
    final created = await ref.read(adjustmentsRepositoryProvider).create(bookingId, input, key: _createKey!);
    _createKey = null;
    _createFingerprint = null;
    await refresh();
    return created;
  }

  Future<void> answer(Adjustment a, {required bool approve}) async {
    final verb = approve ? 'approve' : 'reject';
    final key = _keys.putIfAbsent('${a.id}:$verb', newIdempotencyKey);
    final repo = ref.read(adjustmentsRepositoryProvider);
    if (approve) {
      await repo.approve(bookingId, a.id, key: key);
    } else {
      await repo.reject(bookingId, a.id, key: key);
    }
    _keys.remove('${a.id}:$verb');
    await refresh();
    unawaited(ref.read(jobTrackerProvider(bookingId).notifier).refresh());
  }

  Future<void> withdraw(Adjustment a) async {
    final key = _keys.putIfAbsent('${a.id}:withdraw', newIdempotencyKey);
    await ref.read(adjustmentsRepositoryProvider).withdraw(bookingId, a.id, key: key);
    _keys.remove('${a.id}:withdraw');
    await refresh();
  }
}

final jobAdjustmentsProvider = AsyncNotifierProvider.autoDispose.family<JobAdjustmentsNotifier, List<Adjustment>, String>(
  JobAdjustmentsNotifier.new,
  retry: _noAutoRetry,
);

final jobReceiptProvider = FutureProvider.autoDispose.family<TripReceipt, String>((ref, bookingId) {
  ref.watch(sessionStatusProvider);
  return ref.read(adjustmentsRepositoryProvider).receipt(bookingId);
}, retry: _noAutoRetry);
