import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import '../enums/enums.dart';
import 'finance_models.dart';

ApiException _invalid() => const ApiException(kind: ApiFailureKind.server, code: 'invalid_response', message: null);

Map<String, dynamic> _obj(ApiResult<Object?> r) {
  final data = r.data;
  if (data is! Map) throw _invalid();
  return Map<String, dynamic>.from(data);
}

/// How the customer pays: a saved method, or a method type (and the wallet phone for a mobile wallet).
class PayWith {
  const PayWith({this.paymentMethodId, this.method, this.walletPhone});

  final String? paymentMethodId;
  final PaymentMethodType? method;
  final String? walletPhone;

  Map<String, Object?> toJson() => {
    'payment_method_id': ?paymentMethodId,
    if (method != null) 'method': method!.value,
    'wallet_phone': ?walletPhone,
  };
}

/// A pending saved method plus the gateway page to finish the setup on.
class MethodSetup {
  const MethodSetup({required this.method, this.action});

  final SavedPaymentMethod method;
  final PaymentAction? action;
}

/// Customer payments, wallet and coupons (docs/API.md 8.15, 8.16, 8.17).
/// Every write takes the Idempotency-Key of its user intent; the caller keeps
/// it across retries. Nothing here can mark a payment paid: the server does,
/// from the gateway's verified answer.
abstract interface class PaymentsRepository {
  Future<PaymentInfo> startPayment(String bookingId, {required String key, PayWith pay = const PayWith()});

  Future<PaymentInfo> tip(String bookingId, {required String key, required int amountMinor, PayWith pay = const PayWith()});

  /// Asks the platform to re-check the gateway (read-only until the gateway says paid).
  Future<PaymentInfo> refresh(String paymentId);

  /// Pays the wallet debt (unpaid cancellation fees) through the gateway.
  Future<PaymentInfo> settleDebt({required String key, PayWith pay = const PayWith()});

  Future<List<SavedPaymentMethod>> methods();

  Future<MethodSetup> setupMethod(PaymentMethodType type, {required String key});

  Future<void> deleteMethod(String id, {required String key});

  Future<List<WalletInfo>> wallets();

  Future<PagedList<WalletLine>> walletLines({int page = 1});

  Future<CouponQuote> validateCoupon({
    required String code,
    required int serviceCategoryId,
    required int fareMinor,
    PaymentMethodType? paymentMethod,
    double? lat,
    double? lng,
  });

  Future<MyCoupons> myCoupons();
}

class PaymentsRemoteDataSource {
  const PaymentsRemoteDataSource(this.api);

  final ApiClient api;

  Future<ApiResult<Object?>> start(String bookingId, String key, PayWith pay) =>
      api.post('/bookings/$bookingId/payment-intents', body: pay.toJson(), idempotencyKey: key);

  Future<ApiResult<Object?>> tip(String bookingId, String key, int amountMinor, PayWith pay) =>
      api.post('/bookings/$bookingId/tips', body: {'amount_minor': amountMinor, ...pay.toJson()}, idempotencyKey: key);

  Future<ApiResult<Object?>> refresh(String paymentId) => api.post('/payments/$paymentId/refresh');

  Future<ApiResult<Object?>> settleDebt(String key, PayWith pay) =>
      api.post('/me/wallet/settle-debt', body: pay.toJson(), idempotencyKey: key);

  Future<ApiResult<Object?>> methods() => api.get('/me/payment-methods');

  Future<ApiResult<Object?>> setup(String type, String key) =>
      api.post('/me/payment-methods/setup-intent', body: {'type': type}, idempotencyKey: key);

  Future<ApiResult<Object?>> deleteMethod(String id, String key) =>
      api.delete('/me/payment-methods/$id', idempotencyKey: key);

  Future<ApiResult<Object?>> wallets() => api.get('/me/wallet');

  Future<ApiResult<Object?>> walletLines(int page) => api.get('/me/wallet/transactions', query: {'page': page});

  Future<ApiResult<Object?>> validate(Map<String, Object?> body) => api.post('/coupons/validate', body: body);

  Future<ApiResult<Object?>> coupons() => api.get('/me/coupons');
}

class ApiPaymentsRepository implements PaymentsRepository {
  const ApiPaymentsRepository(this._remote);

  final PaymentsRemoteDataSource _remote;

  @override
  Future<PaymentInfo> startPayment(String bookingId, {required String key, PayWith pay = const PayWith()}) async =>
      PaymentInfo.fromJson(_obj(await _remote.start(bookingId, key, pay)));

  @override
  Future<PaymentInfo> tip(String bookingId, {required String key, required int amountMinor, PayWith pay = const PayWith()}) async =>
      PaymentInfo.fromJson(_obj(await _remote.tip(bookingId, key, amountMinor, pay)));

  @override
  Future<PaymentInfo> refresh(String paymentId) async => PaymentInfo.fromJson(_obj(await _remote.refresh(paymentId)));

  @override
  Future<PaymentInfo> settleDebt({required String key, PayWith pay = const PayWith()}) async =>
      PaymentInfo.fromJson(_obj(await _remote.settleDebt(key, pay)));

  @override
  Future<List<SavedPaymentMethod>> methods() async => [
    for (final e in asMapList((await _remote.methods()).data)) SavedPaymentMethod.fromJson(e),
  ];

  @override
  Future<MethodSetup> setupMethod(PaymentMethodType type, {required String key}) async {
    final m = _obj(await _remote.setup(type.value, key));
    return MethodSetup(
      method: SavedPaymentMethod.fromJson(asMap(m['payment_method'])),
      action: PaymentAction.parse(m['action']),
    );
  }

  @override
  Future<void> deleteMethod(String id, {required String key}) async {
    await _remote.deleteMethod(id, key);
  }

  @override
  Future<List<WalletInfo>> wallets() async => [
    for (final e in asMapList((await _remote.wallets()).data)) WalletInfo.fromJson(e),
  ];

  @override
  Future<PagedList<WalletLine>> walletLines({int page = 1}) async {
    final r = await _remote.walletLines(page);
    return PagedList.parse(r.data, r.meta, WalletLine.fromJson);
  }

  @override
  Future<CouponQuote> validateCoupon({
    required String code,
    required int serviceCategoryId,
    required int fareMinor,
    PaymentMethodType? paymentMethod,
    double? lat,
    double? lng,
  }) async => CouponQuote.fromJson(
    _obj(
      await _remote.validate({
        'coupon_code': code,
        'service_category_id': serviceCategoryId,
        'proposed_fare_minor': fareMinor,
        if (paymentMethod != null) 'payment_method': paymentMethod.value,
        if (lat != null && lng != null) 'pickup': {'lat': lat, 'lng': lng},
      }),
    ),
  );

  @override
  Future<MyCoupons> myCoupons() async => MyCoupons.fromJson(_obj(await _remote.coupons()));
}

// ---------------------------------------------------------------------------
// Adjustments and receipts (both apps; the driver app uses the /provider prefix)

abstract interface class AdjustmentsRepository {
  Future<List<Adjustment>> list(String bookingId);

  Future<Adjustment> create(String bookingId, NewAdjustmentInput input, {required String key});

  Future<Adjustment> approve(String bookingId, String adjustmentId, {required String key, String? note});

  Future<Adjustment> reject(String bookingId, String adjustmentId, {required String key, String? note});

  Future<Adjustment> withdraw(String bookingId, String adjustmentId, {required String key});

  Future<TripReceipt> receipt(String bookingId);
}

class ApiAdjustmentsRepository implements AdjustmentsRepository {
  const ApiAdjustmentsRepository(this._api, {required this.bookingPrefix});

  final ApiClient _api;

  /// `/bookings` (customer) or `/provider/bookings` (driver).
  final String bookingPrefix;

  @override
  Future<List<Adjustment>> list(String bookingId) async => [
    for (final e in asMapList((await _api.get('$bookingPrefix/$bookingId/adjustments')).data)) Adjustment.fromJson(e),
  ];

  @override
  Future<Adjustment> create(String bookingId, NewAdjustmentInput input, {required String key}) async => Adjustment.fromJson(
    _obj(
      await _api.post(
        '$bookingPrefix/$bookingId/adjustments',
        idempotencyKey: key,
        body: {
          'type': input.type.value,
          'amount_minor': ?input.amountMinor,
          'reason': input.reason,
          if (input.scopeItem != null || input.helpers != null || input.floors != null)
            'details': {
              'scope_item': ?input.scopeItem,
              'helpers': ?input.helpers,
              'floors': ?input.floors,
            },
          if ((input.evidenceReference != null && input.evidenceReference!.isNotEmpty) ||
              (input.note != null && input.note!.isNotEmpty))
            'evidence': {
              'reference': (input.evidenceReference != null && input.evidenceReference!.isNotEmpty) ? input.evidenceReference : 'app',
              if (input.note != null && input.note!.isNotEmpty) 'note': input.note,
            },
        },
      ),
    ),
  );

  Future<Adjustment> _answer(String bookingId, String id, String verb, String key, String? note) async => Adjustment.fromJson(
    _obj(
      await _api.post(
        '$bookingPrefix/$bookingId/adjustments/$id/$verb',
        idempotencyKey: key,
        body: {if (note != null && note.isNotEmpty) 'note': note},
      ),
    ),
  );

  @override
  Future<Adjustment> approve(String bookingId, String adjustmentId, {required String key, String? note}) =>
      _answer(bookingId, adjustmentId, 'approve', key, note);

  @override
  Future<Adjustment> reject(String bookingId, String adjustmentId, {required String key, String? note}) =>
      _answer(bookingId, adjustmentId, 'reject', key, note);

  @override
  Future<Adjustment> withdraw(String bookingId, String adjustmentId, {required String key}) =>
      _answer(bookingId, adjustmentId, 'withdraw', key, null);

  @override
  Future<TripReceipt> receipt(String bookingId) async =>
      TripReceipt.fromJson(_obj(await _api.get('$bookingPrefix/$bookingId/receipt')));
}

// ---------------------------------------------------------------------------
// Provider earnings, wallet, payouts and cash

abstract interface class EarningsRepository {
  Future<EarningsSummary> summary(EarningsPeriod period, {DateTime? date});

  Future<PagedList<EarningLine>> transactions({int page = 1, DateTime? from, DateTime? to});

  Future<PagedList<WalletLine>> walletLines({int page = 1});

  Future<List<PayoutMethod>> payoutMethods();

  Future<PayoutMethod> addPayoutMethod(NewPayoutMethodInput input, {required String key});

  Future<PayoutMethod> updatePayoutMethod(String id, NewPayoutMethodInput input, {required String key});

  Future<PayoutMethod> makeDefault(String id, {required String key});

  Future<void> deletePayoutMethod(String id, {required String key});

  Future<PayoutList> payouts({int page = 1});

  Future<PayoutRequest> payout(String id);

  Future<PayoutRequest> requestPayout({required int amountMinor, required String methodId, required String key});

  Future<PayoutRequest> cancelPayout(String id, {required String key});

  /// The driver confirms the cash of a completed cash booking.
  Future<PaymentInfo> confirmCash(String bookingId, {required String key});
}

class ApiEarningsRepository implements EarningsRepository {
  const ApiEarningsRepository(this._api);

  final ApiClient _api;

  static String _day(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  Future<EarningsSummary> summary(EarningsPeriod period, {DateTime? date}) async => EarningsSummary.fromJson(
    _obj(await _api.get('/provider/earnings/summary', query: {'period': period.value, if (date != null) 'date': _day(date)})),
  );

  @override
  Future<PagedList<EarningLine>> transactions({int page = 1, DateTime? from, DateTime? to}) async {
    final r = await _api.get(
      '/provider/earnings/transactions',
      query: {'page': page, if (from != null) 'from': _day(from), if (to != null) 'to': _day(to)},
    );
    return PagedList.parse(r.data, r.meta, EarningLine.fromJson);
  }

  @override
  Future<PagedList<WalletLine>> walletLines({int page = 1}) async {
    final r = await _api.get('/provider/wallet/transactions', query: {'page': page});
    return PagedList.parse(r.data, r.meta, WalletLine.fromJson);
  }

  @override
  Future<List<PayoutMethod>> payoutMethods() async => [
    for (final e in asMapList((await _api.get('/provider/payout-methods')).data)) PayoutMethod.fromJson(e),
  ];

  @override
  Future<PayoutMethod> addPayoutMethod(NewPayoutMethodInput input, {required String key}) async =>
      PayoutMethod.fromJson(_obj(await _api.post('/provider/payout-methods', body: input.toJson(), idempotencyKey: key)));

  @override
  Future<PayoutMethod> updatePayoutMethod(String id, NewPayoutMethodInput input, {required String key}) async =>
      PayoutMethod.fromJson(_obj(await _api.patch('/provider/payout-methods/$id', body: input.toJson(), idempotencyKey: key)));

  @override
  Future<PayoutMethod> makeDefault(String id, {required String key}) async =>
      PayoutMethod.fromJson(_obj(await _api.post('/provider/payout-methods/$id/default', idempotencyKey: key)));

  @override
  Future<void> deletePayoutMethod(String id, {required String key}) async {
    await _api.delete('/provider/payout-methods/$id', idempotencyKey: key);
  }

  @override
  Future<PayoutList> payouts({int page = 1}) async {
    final r = await _api.get('/provider/payouts', query: {'page': page});
    final balance = asMap(r.meta['balance']);
    return PayoutList(
      page: PagedList.parse(r.data, r.meta, PayoutRequest.fromJson),
      balance: balance.isEmpty ? null : PayoutBalance.fromJson(balance),
    );
  }

  @override
  Future<PayoutRequest> payout(String id) async => PayoutRequest.fromJson(_obj(await _api.get('/provider/payouts/$id')));

  @override
  Future<PayoutRequest> requestPayout({required int amountMinor, required String methodId, required String key}) async =>
      PayoutRequest.fromJson(
        _obj(await _api.post('/provider/payouts', body: {'amount_minor': amountMinor, 'payout_method_id': methodId}, idempotencyKey: key)),
      );

  @override
  Future<PayoutRequest> cancelPayout(String id, {required String key}) async =>
      PayoutRequest.fromJson(_obj(await _api.post('/provider/payouts/$id/cancel', idempotencyKey: key)));

  @override
  Future<PaymentInfo> confirmCash(String bookingId, {required String key}) async =>
      PaymentInfo.fromJson(_obj(await _api.post('/provider/bookings/$bookingId/cash-collected', idempotencyKey: key)));
}
