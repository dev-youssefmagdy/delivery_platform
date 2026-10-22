import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/providers.dart';
import '../../../../core/realtime_providers.dart';
import '../../../booking/presentation/providers/booking_providers.dart';

Duration? _noAutoRetry(int retryCount, Object error) => null;

final paymentsRepositoryProvider = Provider<PaymentsRepository>(
  (ref) => ApiPaymentsRepository(PaymentsRemoteDataSource(ref.watch(apiClientProvider))),
);

final adjustmentsRepositoryProvider = Provider<AdjustmentsRepository>(
  (ref) => ApiAdjustmentsRepository(ref.watch(apiClientProvider), bookingPrefix: '/bookings'),
);

/// Opens the gateway page. Overridden in tests; the default uses the browser.
final gatewayLauncherProvider = Provider<Future<bool> Function(String url)>(
  (ref) => (url) async {
    final uri = Uri.tryParse(url);
    if (uri == null || !(uri.isScheme('https') || uri.isScheme('http'))) return false;
    try {
      return await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
    } on Object {
      try {
        return await launchUrl(uri, mode: LaunchMode.externalApplication);
      } on Object {
        return false;
      }
    }
  },
);

// ---------------------------------------------------------------------------
// Error texts

/// Maps a failure of the money flows to ARB text. The stable error code decides;
/// the server message is only the last fallback.
String paymentErrorText(AppLocalizations l10n, Object error) {
  if (error is! ApiException) return l10n.errorGenericBody;
  if (error.isOffline) return l10n.errorOfflineBody;
  if (error.isSessionExpired) return l10n.errorSessionExpiredBody;
  switch (error.code) {
    case 'payments_not_configured':
    case 'payment_gateway_unavailable':
      return l10n.payErrUnavailable;
    case 'payment_method_unsupported':
    case 'payment_method_unavailable':
    case 'payment_method_not_found':
      return l10n.payErrMethod;
    case 'booking_not_payable':
      return l10n.payErrNotPayable;
    case 'payment_nothing_to_pay':
      return l10n.payErrNothingToPay;
    case 'payment_needs_review':
      return l10n.payErrNeedsReview;
    case 'wallet_phone_required':
      return l10n.payErrWalletPhone;
    case 'payment_setup_not_supported':
      return l10n.pmErrSetupUnsupported;
    case 'tips_not_available':
    case 'tip_booking_not_completed':
      return l10n.tipErrNotAvailable;
    case 'tip_amount_invalid':
      return l10n.tipErrInvalid;
    case 'tip_amount_too_large':
      return l10n.tipErrTooLarge;
    case 'tip_already_given':
      return l10n.tipErrAlreadyGiven;
    case 'adjustment_scope_item_required':
    case 'adjustment_scope_item_invalid':
      return l10n.adjErrScopeItem;
    case 'adjustment_within_scope':
      return l10n.adjErrWithinScope;
    case 'adjustment_floors_required':
      return l10n.adjErrFloors;
    case 'adjustment_helpers_invalid':
      return l10n.adjErrHelpers;
    case 'adjustment_amount_invalid':
      return l10n.adjErrAmount;
    case 'adjustment_not_allowed_now':
    case 'adjustment_open_limit':
    case 'adjustment_type_not_allowed':
      return l10n.adjErrNotNow;
    case 'adjustment_not_pending':
    case 'adjustment_expired':
      return l10n.adjErrNotPending;
    case 'adjustment_needs_counterpart':
      return l10n.adjErrCounterpart;
    case 'idempotency_request_in_progress':
      return l10n.bkErrInProgress;
    case 'too_many_requests':
      return l10n.otpErrorTooManyRequests(error.retryAfterSeconds ?? 60);
  }
  return error.message ?? l10n.errorGenericBody;
}

/// Why the gateway declined, for a failed payment; unknown codes get the generic text.
String paymentFailureText(AppLocalizations l10n, String? code) => switch (code) {
  'insufficient_funds' => l10n.payFailInsufficient,
  'card_expired' => l10n.payFailExpired,
  'authentication_failed' || '3ds_failed' => l10n.payFailAuth,
  'cancelled' || 'canceled' || 'user_cancelled' => l10n.payFailCancelled,
  'timeout' || 'expired' => l10n.payFailTimeout,
  _ => l10n.payFailDeclined,
};

/// The explanation of a coupon decision (`reason_code`); the server message is the fallback for unknown codes.
String couponReasonText(AppLocalizations l10n, String code, {String? fallback}) => switch (code) {
  'eligible' => l10n.cpnReasonEligible,
  'not_found' => l10n.cpnReasonNotFound,
  'not_active' => l10n.cpnReasonNotActive,
  'not_started' => l10n.cpnReasonNotStarted,
  'expired' => l10n.cpnReasonExpired,
  'exhausted' || 'global_limit_reached' || 'budget_exhausted' => l10n.cpnReasonExhausted,
  'category_not_eligible' ||
  'service_family_not_covered' ||
  'service_category_not_covered' ||
  'service_excluded' ||
  'vehicle_not_covered' => l10n.cpnReasonService,
  'city_not_covered' || 'zone_not_covered' => l10n.cpnReasonArea,
  'payment_method_not_covered' => l10n.cpnReasonPayment,
  'new_customers_only' => l10n.cpnReasonNewOnly,
  'returning_customers_only' => l10n.cpnReasonReturningOnly,
  'outside_schedule' => l10n.cpnReasonSchedule,
  'below_minimum_fare' => l10n.cpnReasonMinimum,
  'customer_limit_reached' => l10n.cpnReasonCustomerLimit,
  'stacking_not_allowed' => l10n.cpnReasonStacking,
  'currency_mismatch' => l10n.cpnReasonCurrency,
  'no_discount' => l10n.cpnReasonNoDiscount,
  _ => fallback ?? l10n.cpnReasonUnknown,
};

// ---------------------------------------------------------------------------
// Wallet

class WalletState {
  const WalletState({
    required this.wallets,
    required this.lines,
    required this.page,
    required this.hasMore,
    this.loadingMore = false,
  });

  final List<WalletInfo> wallets;
  final List<WalletLine> lines;
  final int page;
  final bool hasMore;
  final bool loadingMore;

  /// The wallet in debt first, otherwise the first one.
  WalletInfo? get primary {
    if (wallets.isEmpty) return null;
    for (final w in wallets) {
      if (w.inDebt) return w;
    }
    return wallets.first;
  }

  WalletState copyWith({List<WalletLine>? lines, int? page, bool? hasMore, bool? loadingMore}) => WalletState(
    wallets: wallets,
    lines: lines ?? this.lines,
    page: page ?? this.page,
    hasMore: hasMore ?? this.hasMore,
    loadingMore: loadingMore ?? this.loadingMore,
  );
}

/// The wallet balance and statement. Refetched on payment hints and reconnects.
class WalletNotifier extends AsyncNotifier<WalletState> {
  @override
  Future<WalletState> build() async {
    ref.watch(sessionStatusProvider);
    final hub = ref.watch(customerEventsProvider);
    final subs = <StreamSubscription<Object?>>[
      hub.events.listen((e) {
        if (e.name == 'payment.status_changed' || e.name == 'booking.adjustment_changed') unawaited(refresh());
      }),
      hub.reconnected.listen((_) => unawaited(refresh())),
    ];
    ref.onDispose(() {
      for (final s in subs) {
        s.cancel();
      }
    });
    return _load();
  }

  Future<WalletState> _load() async {
    final repo = ref.read(paymentsRepositoryProvider);
    final results = await Future.wait([repo.wallets(), repo.walletLines()]);
    final page = results[1] as PagedList<WalletLine>;
    return WalletState(
      wallets: results[0] as List<WalletInfo>,
      lines: page.items,
      page: page.currentPage,
      hasMore: page.hasMore,
    );
  }

  Future<void> reload() async {
    state = const AsyncLoading<WalletState>();
    try {
      state = AsyncData(await _load());
    } on Object catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  /// Keeps what is on screen when the refresh fails.
  Future<void> refresh() async {
    try {
      state = AsyncData(await _load());
    } on Object {
      // The offline banner and the next hint cover it.
    }
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(current.copyWith(loadingMore: true));
    try {
      final next = await ref.read(paymentsRepositoryProvider).walletLines(page: current.page + 1);
      state = AsyncData(
        current.copyWith(lines: [...current.lines, ...next.items], page: next.currentPage, hasMore: next.hasMore, loadingMore: false),
      );
    } on Object {
      state = AsyncData(current.copyWith(loadingMore: false));
    }
  }
}

final walletProvider = AsyncNotifierProvider<WalletNotifier, WalletState>(WalletNotifier.new, retry: _noAutoRetry);

// ---------------------------------------------------------------------------
// Saved payment methods

class PaymentMethodsNotifier extends AsyncNotifier<List<SavedPaymentMethod>> {
  final _deleteKeys = <String, String>{};
  String? _setupKey;
  PaymentMethodType? _setupType;

  @override
  Future<List<SavedPaymentMethod>> build() {
    ref.watch(sessionStatusProvider);
    return ref.read(paymentsRepositoryProvider).methods();
  }

  Future<void> reload() async {
    state = const AsyncLoading<List<SavedPaymentMethod>>();
    try {
      state = AsyncData(await ref.read(paymentsRepositoryProvider).methods());
    } on Object catch (e, st) {
      state = AsyncError(e, st);
    }
  }

  Future<void> refresh() async {
    try {
      state = AsyncData(await ref.read(paymentsRepositoryProvider).methods());
    } on Object {
      // Keep the list on screen.
    }
  }

  /// Starts the gateway setup of a new saved method. One key per intent: a
  /// retry of the same type reuses it. The method becomes active only when the
  /// gateway's verified webhook says so, so the list is refreshed afterwards.
  Future<MethodSetup> setup(PaymentMethodType type) async {
    if (_setupType != type || _setupKey == null) {
      _setupType = type;
      _setupKey = newIdempotencyKey();
    }
    final result = await ref.read(paymentsRepositoryProvider).setupMethod(type, key: _setupKey!);
    _setupKey = null;
    _setupType = null;
    await refresh();
    return result;
  }

  Future<void> delete(String id) async {
    final key = _deleteKeys.putIfAbsent(id, newIdempotencyKey);
    await ref.read(paymentsRepositoryProvider).deleteMethod(id, key: key);
    _deleteKeys.remove(id);
    final current = state.value;
    if (current != null) state = AsyncData([for (final m in current) if (m.id != id) m]);
  }
}

final paymentMethodsProvider = AsyncNotifierProvider<PaymentMethodsNotifier, List<SavedPaymentMethod>>(
  PaymentMethodsNotifier.new,
  retry: _noAutoRetry,
);

// ---------------------------------------------------------------------------
// Coupons

final myCouponsProvider = FutureProvider.autoDispose<MyCoupons>((ref) {
  ref.watch(sessionStatusProvider);
  return ref.read(paymentsRepositoryProvider).myCoupons();
}, retry: _noAutoRetry);

/// The booking form's coupon check: a server quote, nothing reserved.
class CouponCheck {
  const CouponCheck({this.code = '', this.checking = false, this.quote, this.error});

  final String code;
  final bool checking;
  final CouponQuote? quote;
  final ApiException? error;

  bool get applied => quote?.valid == true;
}

class CouponCheckNotifier extends Notifier<CouponCheck> {
  @override
  CouponCheck build() => const CouponCheck();

  void setCode(String code) => state = CouponCheck(code: code.trim().toUpperCase());

  /// A different fare, method or route makes the quote stale; the code stays.
  void invalidateQuote() {
    if (state.quote != null || state.error != null) state = CouponCheck(code: state.code);
  }

  void clear() => state = const CouponCheck();

  Future<void> check({
    required int serviceCategoryId,
    required int fareMinor,
    required PaymentMethodType paymentMethod,
    double? lat,
    double? lng,
  }) async {
    final code = state.code;
    if (code.isEmpty || state.checking) return;
    state = CouponCheck(code: code, checking: true);
    try {
      final quote = await ref
          .read(paymentsRepositoryProvider)
          .validateCoupon(
            code: code,
            serviceCategoryId: serviceCategoryId,
            fareMinor: fareMinor,
            paymentMethod: paymentMethod,
            lat: lat,
            lng: lng,
          );
      state = CouponCheck(code: code, quote: quote);
    } on ApiException catch (e) {
      state = CouponCheck(code: code, error: e);
    }
  }
}

final couponCheckProvider = NotifierProvider<CouponCheckNotifier, CouponCheck>(CouponCheckNotifier.new);

// ---------------------------------------------------------------------------
// Paying: trip, tip and wallet debt

enum PayKind { trip, tip, debt }

@immutable
class PayArgs {
  const PayArgs.trip(String this.bookingId) : kind = PayKind.trip, tipMinor = null;
  const PayArgs.tip(String this.bookingId, int this.tipMinor) : kind = PayKind.tip;
  const PayArgs.debt() : kind = PayKind.debt, bookingId = null, tipMinor = null;

  final PayKind kind;
  final String? bookingId;
  final int? tipMinor;

  @override
  bool operator ==(Object other) =>
      other is PayArgs && other.kind == kind && other.bookingId == bookingId && other.tipMinor == tipMinor;

  @override
  int get hashCode => Object.hash(kind, bookingId, tipMinor);
}

enum PayPhase {
  /// Nothing started (or the person is choosing how to pay).
  idle,

  /// Creating the payment on the server.
  starting,

  /// The gateway page is (or was) open; we are waiting for the server to say paid.
  awaiting,

  /// The server confirmed it (verified gateway answer).
  paid,

  /// The gateway declined or the attempt expired: recoverable with a new attempt.
  failed,

  /// An operator must look at it; no new attempt is possible.
  review,

  /// The request itself failed (offline, validation, gateway down); nothing was charged.
  error,
}

class PayFlowState {
  const PayFlowState({this.phase = PayPhase.idle, this.payment, this.error, this.openUrl, this.checking = false});

  final PayPhase phase;
  final PaymentInfo? payment;
  final ApiException? error;

  /// A gateway page the screen must open once (then calls `consumeUrl`).
  final String? openUrl;
  final bool checking;

  PayFlowState copyWith({PayPhase? phase, PaymentInfo? payment, ApiException? error, String? openUrl, bool? checking, bool clearUrl = false, bool clearError = false}) =>
      PayFlowState(
        phase: phase ?? this.phase,
        payment: payment ?? this.payment,
        error: clearError ? null : (error ?? this.error),
        openUrl: clearUrl ? null : (openUrl ?? this.openUrl),
        checking: checking ?? this.checking,
      );
}

/// One payment intent: start (idempotent per intent), open the gateway page,
/// then wait for the SERVER to report the result (realtime hint, polling and
/// `POST /payments/{id}/refresh`). Returning from the gateway page proves
/// nothing; only a payment the server reports as paid ends in [PayPhase.paid].
class PaymentFlowNotifier extends Notifier<PayFlowState> {
  PaymentFlowNotifier(this.args);

  final PayArgs args;

  String? _key;
  String? _keyFor;
  Timer? _poll;
  int _polls = 0;

  static const _maxPolls = 120;

  PaymentsRepository get _repo => ref.read(paymentsRepositoryProvider);

  @override
  PayFlowState build() {
    final hub = ref.watch(customerEventsProvider);
    final subs = <StreamSubscription<Object?>>[
      hub.events.listen((e) {
        if (e.name != 'payment.status_changed') return;
        final booking = e.data['booking_uuid'];
        if (args.bookingId == null || booking == args.bookingId) unawaited(check());
      }),
      hub.reconnected.listen((_) => unawaited(check())),
    ];
    ref.onDispose(() {
      _poll?.cancel();
      for (final s in subs) {
        s.cancel();
      }
    });
    return const PayFlowState();
  }

  String _fingerprint(PayWith pay) => '${args.kind.name}|${args.bookingId}|${args.tipMinor}|${pay.paymentMethodId}|${pay.method?.value}|${pay.walletPhone}';

  /// Creates (or resumes) the payment. [openGateway] false only re-reads the
  /// state of an attempt that may already be paid (screen reopened).
  Future<void> start(PayWith pay, {bool openGateway = true}) async {
    if (state.phase == PayPhase.starting) return;
    final fingerprint = _fingerprint(pay);
    if (_key == null || _keyFor != fingerprint) {
      _key = newIdempotencyKey();
      _keyFor = fingerprint;
    }
    state = state.copyWith(phase: PayPhase.starting, clearError: true, clearUrl: true);
    try {
      final payment = switch (args.kind) {
        PayKind.trip => await _repo.startPayment(args.bookingId!, key: _key!, pay: pay),
        PayKind.tip => await _repo.tip(args.bookingId!, key: _key!, amountMinor: args.tipMinor!, pay: pay),
        PayKind.debt => await _repo.settleDebt(key: _key!, pay: pay),
      };
      _apply(payment, openGateway: openGateway);
    } on ApiException catch (e) {
      state = state.copyWith(phase: PayPhase.error, error: e);
    }
  }

  void _apply(PaymentInfo payment, {bool openGateway = false}) {
    if (payment.isPaid) {
      _poll?.cancel();
      state = PayFlowState(phase: PayPhase.paid, payment: payment);
      _afterPaid();
    } else if (payment.isFailed) {
      _poll?.cancel();
      // The next start is a new intent (a new attempt on the server).
      _key = null;
      state = PayFlowState(phase: PayPhase.failed, payment: payment);
    } else if (payment.needsReview) {
      _poll?.cancel();
      state = PayFlowState(phase: PayPhase.review, payment: payment);
    } else {
      final url = openGateway ? payment.action?.url : null;
      state = PayFlowState(phase: PayPhase.awaiting, payment: payment, openUrl: url);
      _schedulePoll();
    }
  }

  void _afterPaid() {
    if (args.bookingId != null) ref.invalidate(bookingTrackerProvider(args.bookingId!));
    ref.invalidate(walletProvider);
  }

  void _schedulePoll() {
    _poll?.cancel();
    if (_polls >= _maxPolls) return;
    // 3 s at first, backing off to 15 s: the realtime hint normally arrives first.
    final seconds = _polls < 10 ? 3 : (_polls < 30 ? 6 : 15);
    _poll = Timer(Duration(seconds: seconds), () {
      _polls++;
      unawaited(check());
    });
  }

  /// Asks the server for the current state of the open attempt.
  Future<void> check() async {
    final payment = state.payment;
    if (payment == null || state.phase != PayPhase.awaiting) return;
    state = state.copyWith(checking: true);
    try {
      _apply(await _repo.refresh(payment.id));
    } on ApiException {
      // Offline or throttled: keep waiting, the next poll or hint retries.
      state = state.copyWith(checking: false);
      _schedulePoll();
    }
  }

  void consumeUrl() {
    if (state.openUrl != null) state = state.copyWith(clearUrl: true);
  }

  /// Back to choosing a method (after a failure or an error).
  void reset() {
    _poll?.cancel();
    _polls = 0;
    state = const PayFlowState();
  }
}

final paymentFlowProvider = NotifierProvider.autoDispose.family<PaymentFlowNotifier, PayFlowState, PayArgs>(PaymentFlowNotifier.new);

// ---------------------------------------------------------------------------
// Adjustments of one booking

class AdjustmentsNotifier extends AsyncNotifier<List<Adjustment>> {
  AdjustmentsNotifier(this.bookingId);

  final String bookingId;
  final _keys = <String, String>{};

  @override
  Future<List<Adjustment>> build() async {
    ref.watch(sessionStatusProvider);
    final hub = ref.watch(customerEventsProvider);
    final subs = <StreamSubscription<Object?>>[
      hub.events.listen((e) {
        if (e.name == 'booking.adjustment_changed' && e.data['booking_uuid'] == bookingId) unawaited(refresh());
      }),
      hub.reconnected.listen((_) => unawaited(refresh())),
    ];
    final timer = Timer.periodic(const Duration(seconds: 30), (_) => unawaited(refresh()));
    ref.onDispose(() {
      timer.cancel();
      for (final s in subs) {
        s.cancel();
      }
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

  /// Asks for a change (moving / freight: extra helper, scope change). One key
  /// per identical request, reused on retry until it succeeds or is refused.
  Future<Adjustment> request(NewAdjustmentInput input) async {
    final intent =
        'new:${input.type.value}:${input.scopeItem}:${input.helpers}:${input.floors}:${input.amountMinor}:${input.reason}';
    final key = _keys.putIfAbsent(intent, newIdempotencyKey);
    try {
      final saved = await ref.read(adjustmentsRepositoryProvider).create(bookingId, input, key: key);
      _keys.remove(intent);
      final current = state.value ?? const <Adjustment>[];
      state = AsyncData([saved, ...current]);
      return saved;
    } on ApiException catch (e) {
      if (!(e.isOffline || e.kind == ApiFailureKind.server || e.kind == ApiFailureKind.rateLimited)) {
        _keys.remove(intent);
      }
      rethrow;
    }
  }

  /// Approves or rejects. One key per (adjustment, verb) intent, reused on retry.
  Future<void> answer(Adjustment adjustment, {required bool approve, String? note}) async {
    final verb = approve ? 'approve' : 'reject';
    final key = _keys.putIfAbsent('${adjustment.id}:$verb', newIdempotencyKey);
    final repo = ref.read(adjustmentsRepositoryProvider);
    final saved = approve
        ? await repo.approve(bookingId, adjustment.id, key: key, note: note)
        : await repo.reject(bookingId, adjustment.id, key: key, note: note);
    _keys.remove('${adjustment.id}:$verb');
    final current = state.value;
    if (current != null) {
      state = AsyncData([for (final a in current) a.id == saved.id ? saved : a]);
    }
    ref.invalidate(bookingTrackerProvider(bookingId));
  }
}

final adjustmentsProvider = AsyncNotifierProvider.autoDispose.family<AdjustmentsNotifier, List<Adjustment>, String>(
  AdjustmentsNotifier.new,
  retry: _noAutoRetry,
);

final receiptProvider = FutureProvider.autoDispose.family<TripReceipt, String>((ref, bookingId) {
  ref.watch(sessionStatusProvider);
  return ref.read(adjustmentsRepositoryProvider).receipt(bookingId);
}, retry: _noAutoRetry);
