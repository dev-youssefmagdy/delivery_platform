/// Models of payments, wallet, coupons, receipts, adjustments, earnings and
/// payouts (docs/API.md 8.15-8.17 and P3.5). Money is always integer minor
/// units; parsing is tolerant so a new server field or enum value never
/// crashes a screen.
library;

import '../enums/enums.dart';
import '../forms/form_schema.dart';
import '../money/money.dart';

Map<String, dynamic> asMap(Object? v) =>
    v is Map ? Map<String, dynamic>.from(v) : const {};

List<Map<String, dynamic>> asMapList(Object? v) => [
  if (v is List)
    for (final e in v)
      if (e is Map) Map<String, dynamic>.from(e),
];

DateTime? asTime(Object? v) =>
    v is String ? DateTime.tryParse(v)?.toLocal() : null;

int asInt(Object? v, [int fallback = 0]) => v is num
    ? v.toInt()
    : (v is String ? int.tryParse(v) ?? fallback : fallback);

/// `{amount_minor | minor, currency}` -> [Money], or null when absent.
Money? moneyOrNull(Object? v) {
  if (v is! Map) return null;
  final minor = v['amount_minor'] ?? v['minor'];
  final currency = v['currency'];
  if (minor is! num || currency is! String) return null;
  return Money(minor: minor.toInt(), currency: currency);
}

Money moneyOf(Object? v, String currency) =>
    moneyOrNull(v) ?? Money(minor: 0, currency: currency);

/// An integer minor-unit field of a flat map ("..._minor") as [Money].
Money minorOf(Object? minor, String currency) =>
    Money(minor: asInt(minor), currency: currency);

class PagedList<T> {
  const PagedList({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<T> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  static PagedList<T> parse<T>(
    Object? data,
    Map<String, dynamic> meta,
    T Function(Map<String, dynamic>) map,
  ) {
    final pagination = asMap(meta['pagination']);
    return PagedList(
      items: [for (final e in asMapList(data)) map(e)],
      currentPage: asInt(pagination['current_page'], 1),
      lastPage: asInt(pagination['last_page'], 1),
    );
  }
}

// ---------------------------------------------------------------------------
// Payments

class PaymentAction {
  const PaymentAction({this.clientSecret, this.url});

  final String? clientSecret;

  /// Gateway page to open (hosted checkout / 3-D Secure / wallet approval).
  final String? url;

  static PaymentAction? parse(Object? v) {
    final m = asMap(v);
    if (m.isEmpty) return null;
    return PaymentAction(
      clientSecret: m['client_secret'] as String?,
      url: m['url'] as String?,
    );
  }
}

/// Which kind of charge a payment is (`trip`, `tip`, `debt`).
enum PaymentKindType {
  trip('trip'),
  tip('tip'),
  debt('debt'),
  unknown('');

  const PaymentKindType(this.value);
  final String value;

  static PaymentKindType parse(String? raw) =>
      values.firstWhere((k) => k.value == raw, orElse: () => unknown);
}

class PaymentInfo {
  const PaymentInfo({
    required this.id,
    required this.bookingId,
    required this.kind,
    required this.method,
    required this.status,
    required this.amount,
    this.statusLabel,
    this.refundedMinor = 0,
    this.attempt = 1,
    this.failureCode,
    this.savedMethodId,
    this.savedMethodLabel,
    this.expiresAt,
    this.paidAt,
    this.createdAt,
    this.action,
  });

  factory PaymentInfo.fromJson(Map<String, dynamic> json) {
    final status = asMap(json['status']);
    final saved = asMap(json['saved_method']);
    return PaymentInfo(
      id: '${json['id']}',
      bookingId: '${json['booking_id'] ?? ''}',
      kind: PaymentKindType.parse(json['kind'] as String?),
      method: PaymentMethodType.parse(json['method'] as String?),
      status: PaymentStatus.parse(status['code'] as String?),
      statusLabel: status['label'] as String?,
      amount:
          moneyOrNull(json['amount']) ?? const Money(minor: 0, currency: ''),
      refundedMinor: asInt(json['refunded_minor']),
      attempt: asInt(json['attempt'], 1),
      failureCode: json['failure_code'] as String?,
      savedMethodId: saved['id'] as String?,
      savedMethodLabel: saved.isEmpty
          ? null
          : '${saved['brand'] ?? ''} ${saved['last4'] ?? ''}'.trim(),
      expiresAt: asTime(json['expires_at']),
      paidAt: asTime(json['paid_at']),
      createdAt: asTime(json['created_at']),
      action: PaymentAction.parse(json['action']),
    );
  }

  final String id;
  final String bookingId;
  final PaymentKindType kind;
  final PaymentMethodType method;
  final PaymentStatus status;
  final String? statusLabel;
  final Money amount;
  final int refundedMinor;
  final int attempt;
  final String? failureCode;
  final String? savedMethodId;
  final String? savedMethodLabel;
  final DateTime? expiresAt;
  final DateTime? paidAt;
  final DateTime? createdAt;
  final PaymentAction? action;

  /// Only the server's verified answer makes a payment paid; the app just displays it.
  bool get isPaid =>
      status == PaymentStatus.paid ||
      status == PaymentStatus.partiallyRefunded ||
      status == PaymentStatus.refunded;

  bool get isFailed => status == PaymentStatus.failed;

  /// An operator must look at it; the customer cannot retry.
  bool get needsReview => status == PaymentStatus.reconciliationRequired;

  /// Still waiting for the gateway (customer may be on the gateway page).
  bool get isWaiting =>
      status == PaymentStatus.pending ||
      status == PaymentStatus.requiresAction ||
      status == PaymentStatus.authorized;
}

class SavedPaymentMethod {
  const SavedPaymentMethod({
    required this.id,
    required this.type,
    required this.status,
    this.brand,
    this.last4,
    this.isDefault = false,
  });

  factory SavedPaymentMethod.fromJson(Map<String, dynamic> json) =>
      SavedPaymentMethod(
        id: '${json['id']}',
        type: PaymentMethodType.parse(json['type'] as String?),
        status: '${json['status'] ?? ''}',
        brand: json['brand'] as String?,
        last4: json['last4'] as String?,
        isDefault: json['is_default'] == true,
      );

  final String id;
  final PaymentMethodType type;
  final String status;
  final String? brand;
  final String? last4;
  final bool isDefault;

  bool get isActive => status == 'active';
  bool get isPending => status == 'pending';
}

// ---------------------------------------------------------------------------
// Wallet

class WalletInfo {
  const WalletInfo({
    required this.id,
    required this.currency,
    required this.balance,
  });

  factory WalletInfo.fromJson(Map<String, dynamic> json) {
    final currency = '${json['currency'] ?? ''}';
    return WalletInfo(
      id: '${json['id']}',
      currency: currency,
      balance: moneyOf(json['balance'], currency),
    );
  }

  final String id;
  final String currency;

  /// Negative = the customer owes unpaid cancellation fees.
  final Money balance;

  bool get inDebt => balance.minor < 0;
  Money get debt =>
      Money(minor: balance.minor < 0 ? -balance.minor : 0, currency: currency);
}

class WalletLine {
  const WalletLine({
    required this.id,
    required this.typeCode,
    required this.typeLabel,
    required this.description,
    required this.change,
    required this.balanceAfter,
    required this.postedAt,
  });

  factory WalletLine.fromJson(Map<String, dynamic> json) {
    final type = asMap(json['type']);
    return WalletLine(
      id: '${json['id']}',
      typeCode: '${type['code'] ?? ''}',
      typeLabel: '${type['label'] ?? ''}',
      description: '${json['description'] ?? ''}',
      change:
          moneyOrNull(json['change']) ?? const Money(minor: 0, currency: ''),
      balanceAfter:
          moneyOrNull(json['balance_after']) ??
          const Money(minor: 0, currency: ''),
      postedAt: asTime(json['posted_at']),
    );
  }

  final String id;
  final String typeCode;
  final String typeLabel;
  final String description;

  /// Signed.
  final Money change;
  final Money balanceAfter;
  final DateTime? postedAt;
}

// ---------------------------------------------------------------------------
// Coupons

class CouponSummary {
  const CouponSummary({
    required this.id,
    required this.title,
    this.code,
    this.automatic = false,
    this.description = const LocalizedText({}),
    this.terms = const LocalizedText({}),
    this.summary,
    this.startsAt,
    this.endsAt,
  });

  factory CouponSummary.fromJson(Map<String, dynamic> json) => CouponSummary(
    id: '${json['id']}',
    code: json['code'] as String?,
    automatic: json['automatic'] == true,
    title: LocalizedText.fromJson(json['title']),
    description: LocalizedText.fromJson(json['description']),
    terms: LocalizedText.fromJson(json['terms']),
    summary: json['summary'] is String ? json['summary'] as String : null,
    startsAt: asTime(json['starts_at']),
    endsAt: asTime(json['ends_at']),
  );

  final String id;
  final String? code;
  final bool automatic;
  final LocalizedText title;
  final LocalizedText description;
  final LocalizedText terms;

  /// Server-rendered discount text, e.g. "20% off".
  final String? summary;
  final DateTime? startsAt;
  final DateTime? endsAt;
}

/// The server's answer to "would this coupon apply?" - never a client decision.
class CouponQuote {
  const CouponQuote({
    required this.valid,
    required this.reasonCode,
    required this.discount,
    this.message,
    this.coupon,
  });

  factory CouponQuote.fromJson(Map<String, dynamic> json) {
    final discount = moneyOrNull(json['discount']);
    return CouponQuote(
      valid: json['valid'] == true,
      reasonCode: '${json['reason_code'] ?? ''}',
      message: json['message'] as String?,
      discount: discount ?? const Money(minor: 0, currency: ''),
      coupon: json['coupon'] is Map
          ? CouponSummary.fromJson(asMap(json['coupon']))
          : null,
    );
  }

  final bool valid;

  /// Stable explanation code (`eligible`, `below_minimum_fare`, ...).
  final String reasonCode;
  final String? message;
  final Money discount;
  final CouponSummary? coupon;
}

/// Statuses of a coupon use (`reserved`, `committed`, `released`, `reversed`).
class CouponUsage {
  const CouponUsage({
    required this.id,
    required this.status,
    required this.code,
    required this.title,
    required this.discount,
    required this.isFinal,
    this.bookingId,
    this.reservedAt,
  });

  factory CouponUsage.fromJson(Map<String, dynamic> json) {
    final coupon = asMap(json['coupon']);
    final discount = asMap(json['discount']);
    return CouponUsage(
      id: '${json['id']}',
      status: '${json['status'] ?? ''}',
      code: coupon['code'] as String?,
      title: LocalizedText.fromJson(coupon['title']),
      discount: moneyOrNull(discount) ?? const Money(minor: 0, currency: ''),
      isFinal: discount['final'] == true,
      bookingId: json['booking'] as String?,
      reservedAt: asTime(json['reserved_at']),
    );
  }

  final String id;
  final String status;
  final String? code;
  final LocalizedText title;
  final Money discount;
  final bool isFinal;
  final String? bookingId;
  final DateTime? reservedAt;
}

class MyCoupons {
  const MyCoupons({required this.available, required this.history});

  factory MyCoupons.fromJson(Map<String, dynamic> json) => MyCoupons(
    available: [
      for (final e in asMapList(json['available'])) CouponSummary.fromJson(e),
    ],
    history: [
      for (final e in asMapList(json['history'])) CouponUsage.fromJson(e),
    ],
  );

  final List<CouponSummary> available;
  final List<CouponUsage> history;
}

// ---------------------------------------------------------------------------
// Adjustments (fare changes after selection) and receipts

enum AdjustmentType {
  extraStop('extra_stop'),
  waiting('waiting'),
  tollParking('toll_parking'),
  scopeChange('scope_change'),
  extraHelper('extra_helper'),
  stairs('stairs'),
  unknown('');

  const AdjustmentType(this.value);
  final String value;

  static AdjustmentType parse(String? raw) =>
      values.firstWhere((t) => t.value == raw, orElse: () => unknown);

  /// The types a person can ask for.
  static const choices = [extraStop, waiting, tollParking, scopeChange];

  /// Moving / freight bookings add two scope-bound types (P4.2).
  static const scopedChoices = [
    extraHelper,
    stairs,
    extraStop,
    waiting,
    tollParking,
    scopeChange,
  ];
}

int? _detailInt(Object? details, String key) {
  final v = details is Map ? details[key] : null;
  return v is num ? v.toInt() : null;
}

class Adjustment {
  const Adjustment({
    required this.id,
    required this.type,
    required this.typeLabel,
    required this.status,
    required this.statusLabel,
    required this.requestedByMe,
    required this.canAnswer,
    required this.canWithdraw,
    required this.amount,
    required this.reason,
    this.oldTotal,
    this.newTotal,
    this.expiresAt,
    this.decisionNote,
    this.createdAt,
    this.scopeItem,
    this.helpers,
    this.floors,
  });

  factory Adjustment.fromJson(Map<String, dynamic> json) => Adjustment(
    id: '${json['uuid']}',
    type: AdjustmentType.parse(json['type'] as String?),
    typeLabel: '${json['type_label'] ?? ''}',
    status: '${json['status'] ?? ''}',
    statusLabel: '${json['status_label'] ?? ''}',
    requestedByMe: json['requested_by_me'] == true,
    canAnswer: json['can_answer'] == true,
    canWithdraw: json['can_withdraw'] == true,
    amount: moneyOrNull(json['amount']) ?? const Money(minor: 0, currency: ''),
    oldTotal: moneyOrNull(json['old_total']),
    newTotal: moneyOrNull(json['new_total']),
    reason: '${json['reason'] ?? ''}',
    expiresAt: asTime(json['expires_at']),
    decisionNote: json['decision_note'] as String?,
    createdAt: asTime(json['created_at']),
    scopeItem:
        (json['details'] is Map ? (json['details'] as Map)['scope_item'] : null)
            as String?,
    helpers: _detailInt(json['details'], 'helpers'),
    floors: _detailInt(json['details'], 'floors'),
  );

  final String id;
  final AdjustmentType type;
  final String typeLabel;
  final String status;
  final String statusLabel;
  final bool requestedByMe;
  final bool canAnswer;
  final bool canWithdraw;
  final Money amount;
  final Money? oldTotal;
  final Money? newTotal;
  final String reason;
  final DateTime? expiresAt;
  final String? decisionNote;
  final DateTime? createdAt;

  /// The scope item of the agreed scope the change refers to (moving /
  /// freight): `helpers`, `stairs`, `extra_stops`, `waiting_allowance`,
  /// `tolls`, `parking`, ...
  final String? scopeItem;
  final int? helpers;
  final int? floors;

  bool get isPending => status == 'pending';
}

class NewAdjustmentInput {
  const NewAdjustmentInput({
    required this.type,
    required this.reason,
    this.amountMinor,
    this.note,
    this.scopeItem,
    this.helpers,
    this.floors,
    this.evidenceReference,
  });

  final AdjustmentType type;
  final String reason;

  /// Scoped bookings need the item of the agreed scope (`details.scope_item`).
  final String? scopeItem;

  /// `extra_helper`: how many (1-5).
  final int? helpers;

  /// `stairs`: how many floors.
  final int? floors;

  /// Receipt number; required by the server for `toll_parking`.
  final String? evidenceReference;

  /// Null for `waiting` when the policy prices it per minute.
  final int? amountMinor;
  final String? note;
}

class ReceiptAdjustmentLine {
  const ReceiptAdjustmentLine({
    required this.type,
    required this.reason,
    required this.amount,
  });

  final String type;
  final String reason;
  final Money amount;
}

/// The immutable receipt of a trip. A customer sees the fare lines; a driver
/// sees the fare and the earnings section (never the customer's fee or tax).
class TripReceipt {
  const TripReceipt({
    required this.number,
    required this.currency,
    required this.issuedAt,
    required this.bookingCode,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.agreed,
    required this.adjustments,
    required this.adjustmentsTotal,
    required this.subtotal,
    this.counterpartName,
    this.pickup,
    this.dropoff,
    this.discount,
    this.customerFee,
    this.taxRateBp,
    this.tax,
    this.tip,
    this.total,
    this.earnings,
    this.htmlUrl,
    this.completedAt,
  });

  factory TripReceipt.fromJson(Map<String, dynamic> json) {
    final currency = '${json['currency'] ?? ''}';
    final booking = asMap(json['booking']);
    final payment = asMap(json['payment']);
    final fare = asMap(json['fare']);
    final parties = asMap(json['parties']);
    final counterpart = asMap(parties['provider'] ?? parties['customer']);
    final earnings = asMap(json['earnings']);
    Money? opt(String key) =>
        fare.containsKey(key) ? minorOf(fare[key], currency) : null;
    return TripReceipt(
      number: '${json['number'] ?? ''}',
      currency: currency,
      issuedAt: asTime(json['issued_at']),
      bookingCode: '${booking['code'] ?? ''}',
      pickup: booking['pickup'] as String?,
      dropoff: booking['dropoff'] as String?,
      completedAt: asTime(booking['completed_at']),
      counterpartName: counterpart['name'] as String?,
      paymentMethod: PaymentMethodType.parse(payment['method'] as String?),
      paymentStatus: PaymentStatus.parse(payment['status'] as String?),
      agreed: minorOf(fare['agreed_minor'], currency),
      adjustments: [
        for (final a in asMapList(fare['adjustments']))
          ReceiptAdjustmentLine(
            type: '${a['type'] ?? ''}',
            reason: '${a['reason'] ?? ''}',
            amount: minorOf(a['amount_minor'], currency),
          ),
      ],
      adjustmentsTotal: minorOf(fare['adjustments_total_minor'], currency),
      subtotal: minorOf(fare['subtotal_minor'], currency),
      discount: opt('discount_minor'),
      customerFee: opt('customer_fee_minor'),
      taxRateBp: fare['tax_rate_bp'] is num ? asInt(fare['tax_rate_bp']) : null,
      tax: opt('tax_minor'),
      tip: opt('tip_minor'),
      total: opt('total_minor'),
      earnings: earnings.isEmpty
          ? null
          : ReceiptEarnings.fromJson(earnings, currency),
      htmlUrl: json['html_url'] as String?,
    );
  }

  final String number;
  final String currency;
  final DateTime? issuedAt;
  final String bookingCode;
  final String? pickup;
  final String? dropoff;
  final DateTime? completedAt;
  final String? counterpartName;
  final PaymentMethodType paymentMethod;
  final PaymentStatus paymentStatus;
  final Money agreed;
  final List<ReceiptAdjustmentLine> adjustments;
  final Money adjustmentsTotal;
  final Money subtotal;
  final Money? discount;
  final Money? customerFee;
  final int? taxRateBp;
  final Money? tax;
  final Money? tip;

  /// What the customer pays (customer view only).
  final Money? total;

  /// Driver view only.
  final ReceiptEarnings? earnings;

  /// Signed printable receipt (opens in the browser).
  final String? htmlUrl;
}

class ReceiptEarnings {
  const ReceiptEarnings({
    required this.gross,
    required this.commission,
    required this.discountProvider,
    required this.net,
    required this.earning,
  });

  factory ReceiptEarnings.fromJson(
    Map<String, dynamic> json,
    String currency,
  ) => ReceiptEarnings(
    gross: minorOf(json['gross_minor'], currency),
    commission: minorOf(json['commission_minor'], currency),
    discountProvider: minorOf(json['discount_provider_minor'], currency),
    net: minorOf(json['net_minor'], currency),
    earning: minorOf(json['earning_minor'], currency),
  );

  final Money gross;
  final Money commission;
  final Money discountProvider;
  final Money net;
  final Money earning;
}

// ---------------------------------------------------------------------------
// Provider earnings, wallet and payouts

enum EarningsPeriod {
  day('day'),
  week('week'),
  month('month');

  const EarningsPeriod(this.value);
  final String value;
}

class CategoryEarnings {
  const CategoryEarnings({
    required this.name,
    required this.jobs,
    required this.gross,
    required this.earnings,
    this.code,
  });

  factory CategoryEarnings.fromJson(
    Map<String, dynamic> json,
    String currency,
  ) => CategoryEarnings(
    code: json['code'] as String?,
    name: LocalizedText.fromJson(json['name']),
    jobs: asInt(json['jobs']),
    gross: minorOf(json['gross_minor'], currency),
    earnings: minorOf(json['earnings_minor'], currency),
  );

  final String? code;
  final LocalizedText name;
  final int jobs;
  final Money gross;
  final Money earnings;
}

class EarningsDay {
  const EarningsDay({
    required this.date,
    required this.jobs,
    required this.earnings,
  });

  final String date;
  final int jobs;
  final Money earnings;
}

/// The provider's wallet position, including the cash-debt state.
class ProviderWalletPosition {
  const ProviderWalletPosition({
    required this.balance,
    required this.reserved,
    required this.available,
    required this.cashDebt,
    required this.cashDebtLimit,
    required this.minPayout,
    required this.maxPayout,
  });

  factory ProviderWalletPosition.fromJson(
    Map<String, dynamic> json,
    String currency,
  ) => ProviderWalletPosition(
    balance: minorOf(json['balance_minor'], currency),
    reserved: minorOf(json['reserved_minor'], currency),
    available: minorOf(json['available_minor'], currency),
    cashDebt: minorOf(json['cash_debt_minor'], currency),
    cashDebtLimit: minorOf(json['cash_debt_limit_minor'], currency),
    minPayout: minorOf(json['min_payout_minor'], currency),
    maxPayout: minorOf(json['max_payout_minor'], currency),
  );

  final Money balance;
  final Money reserved;
  final Money available;
  final Money cashDebt;
  final Money cashDebtLimit;
  final Money minPayout;
  final Money maxPayout;

  bool get hasCashDebt => cashDebt.minor > 0;

  /// Over the limit the server stops sending requests (`cash_debt_limit_exceeded`).
  bool get cashDebtBlocksWork => cashDebt.minor > cashDebtLimit.minor;
}

class EarningsSummary {
  const EarningsSummary({
    required this.period,
    required this.currency,
    required this.jobs,
    required this.gross,
    required this.commission,
    required this.tips,
    required this.net,
    required this.earned,
    required this.cashCollected,
    required this.discountProvider,
    required this.discountPlatform,
    required this.byCategory,
    required this.series,
    required this.wallet,
    this.from,
    this.to,
  });

  factory EarningsSummary.fromJson(Map<String, dynamic> json) {
    final currency = '${json['currency'] ?? ''}';
    final t = asMap(json['totals']);
    return EarningsSummary(
      period: EarningsPeriod.values.firstWhere(
        (p) => p.value == json['period'],
        orElse: () => EarningsPeriod.month,
      ),
      currency: currency,
      from: asTime(json['from']),
      to: asTime(json['to']),
      jobs: asInt(t['jobs']),
      gross: minorOf(t['gross_minor'], currency),
      commission: minorOf(t['commission_minor'], currency),
      tips: minorOf(t['tips_minor'], currency),
      net: minorOf(t['net_minor'], currency),
      earned: minorOf(t['earnings_minor'], currency),
      cashCollected: minorOf(t['cash_collected_minor'], currency),
      discountProvider: minorOf(t['discount_provider_minor'], currency),
      discountPlatform: minorOf(t['discount_platform_minor'], currency),
      byCategory: [
        for (final c in asMapList(json['by_category']))
          CategoryEarnings.fromJson(c, currency),
      ],
      series: [
        for (final d in asMapList(json['series']))
          EarningsDay(
            date: '${d['date']}',
            jobs: asInt(d['jobs']),
            earnings: minorOf(d['earnings_minor'], currency),
          ),
      ],
      wallet: ProviderWalletPosition.fromJson(asMap(json['wallet']), currency),
    );
  }

  final EarningsPeriod period;
  final String currency;
  final DateTime? from;
  final DateTime? to;
  final int jobs;
  final Money gross;
  final Money commission;
  final Money tips;
  final Money net;
  final Money earned;
  final Money cashCollected;
  final Money discountProvider;
  final Money discountPlatform;
  final List<CategoryEarnings> byCategory;
  final List<EarningsDay> series;
  final ProviderWalletPosition wallet;
}

class EarningLine {
  const EarningLine({
    required this.bookingId,
    required this.bookingCode,
    required this.service,
    required this.grossFare,
    required this.commission,
    required this.discountProviderFunded,
    required this.discountPlatformFunded,
    required this.tax,
    required this.tip,
    required this.providerNet,
    required this.providerEarning,
    required this.paymentMethod,
    required this.paymentStatus,
    this.paymentStatusLabel,
    this.cashCollected,
    this.completedAt,
  });

  factory EarningLine.fromJson(Map<String, dynamic> json) {
    final booking = asMap(json['booking']);
    final payment = asMap(json['payment']);
    final zero = const Money(minor: 0, currency: '');
    return EarningLine(
      bookingId: '${booking['id']}',
      bookingCode: '${booking['code'] ?? ''}',
      service: LocalizedText.fromJson(asMap(json['service'])['name']),
      completedAt: asTime(json['completed_at']),
      grossFare: moneyOrNull(json['gross_fare']) ?? zero,
      commission: moneyOrNull(json['commission']) ?? zero,
      discountPlatformFunded:
          moneyOrNull(json['discount_platform_funded']) ?? zero,
      discountProviderFunded:
          moneyOrNull(json['discount_provider_funded']) ?? zero,
      tax: moneyOrNull(json['tax']) ?? zero,
      tip: moneyOrNull(json['tip']) ?? zero,
      providerNet: moneyOrNull(json['provider_net']) ?? zero,
      providerEarning: moneyOrNull(json['provider_earning']) ?? zero,
      cashCollected: moneyOrNull(json['cash_collected']),
      paymentMethod: PaymentMethodType.parse(payment['method'] as String?),
      paymentStatus: PaymentStatus.parse(payment['status'] as String?),
      paymentStatusLabel: payment['status_label'] as String?,
    );
  }

  final String bookingId;
  final String bookingCode;
  final LocalizedText service;
  final DateTime? completedAt;
  final Money grossFare;
  final Money commission;
  final Money discountProviderFunded;
  final Money discountPlatformFunded;
  final Money tax;
  final Money tip;
  final Money providerNet;
  final Money providerEarning;
  final Money? cashCollected;
  final PaymentMethodType paymentMethod;
  final PaymentStatus paymentStatus;
  final String? paymentStatusLabel;
}

enum PayoutMethodKind {
  bankAccount('bank_account'),
  mobileWallet('mobile_wallet'),
  unknown('');

  const PayoutMethodKind(this.value);
  final String value;

  static PayoutMethodKind parse(String? raw) =>
      values.firstWhere((k) => k.value == raw, orElse: () => unknown);
}

class PayoutMethod {
  const PayoutMethod({
    required this.id,
    required this.kind,
    required this.kindLabel,
    required this.label,
    required this.verified,
    required this.isDefault,
    this.last4,
  });

  factory PayoutMethod.fromJson(Map<String, dynamic> json) {
    final type = asMap(json['type']);
    return PayoutMethod(
      id: '${json['id']}',
      kind: PayoutMethodKind.parse(type['code'] as String?),
      kindLabel: '${type['label'] ?? ''}',
      label: '${json['label'] ?? ''}',
      last4: json['last4'] as String?,
      verified: json['verified'] == true,
      isDefault: json['is_default'] == true,
    );
  }

  final String id;
  final PayoutMethodKind kind;
  final String kindLabel;
  final String label;
  final String? last4;
  final bool verified;
  final bool isDefault;
}

class NewPayoutMethodInput {
  const NewPayoutMethodInput({
    required this.kind,
    required this.label,
    required this.holderName,
    this.bankName,
    this.accountNumber,
    this.walletNumber,
    this.isDefault = false,
  });

  final PayoutMethodKind kind;
  final String label;
  final String holderName;
  final String? bankName;
  final String? accountNumber;
  final String? walletNumber;
  final bool isDefault;

  Map<String, Object?> toJson() => {
    'type': kind.value,
    'label': label,
    'details': kind == PayoutMethodKind.bankAccount
        ? {
            'holder_name': holderName,
            'bank_name': bankName,
            'account_number': accountNumber,
          }
        : {'holder_name': holderName, 'wallet_number': walletNumber},
    'is_default': isDefault,
  };
}

class PayoutRequest {
  const PayoutRequest({
    required this.id,
    required this.status,
    required this.statusLabel,
    required this.amount,
    required this.fee,
    required this.net,
    required this.cancellable,
    this.methodLabel,
    this.transferReference,
    this.rejectionReason,
    this.failureReason,
    this.requestedAt,
    this.approvedAt,
    this.paidAt,
    this.closedAt,
  });

  factory PayoutRequest.fromJson(Map<String, dynamic> json) {
    final status = asMap(json['status']);
    final method = asMap(json['method']);
    final zero = const Money(minor: 0, currency: '');
    return PayoutRequest(
      id: '${json['id']}',
      status: PayoutStatus.parse(status['code'] as String?),
      statusLabel: '${status['label'] ?? ''}',
      amount: moneyOrNull(json['amount']) ?? zero,
      fee: moneyOrNull(json['fee']) ?? zero,
      net: moneyOrNull(json['net']) ?? zero,
      cancellable: json['cancellable'] == true,
      methodLabel: [
        method['label'],
        method['last4'],
      ].whereType<String>().where((s) => s.isNotEmpty).join(' '),
      transferReference: json['transfer_reference'] as String?,
      rejectionReason: json['rejection_reason'] as String?,
      failureReason: json['failure_reason'] as String?,
      requestedAt: asTime(json['requested_at']),
      approvedAt: asTime(json['approved_at']),
      paidAt: asTime(json['paid_at']),
      closedAt: asTime(json['closed_at']),
    );
  }

  final String id;
  final PayoutStatus status;
  final String statusLabel;
  final Money amount;
  final Money fee;
  final Money net;
  final bool cancellable;
  final String? methodLabel;
  final String? transferReference;
  final String? rejectionReason;
  final String? failureReason;
  final DateTime? requestedAt;
  final DateTime? approvedAt;
  final DateTime? paidAt;
  final DateTime? closedAt;

  /// Money is back in the wallet.
  bool get returned => const {
    PayoutStatus.rejected,
    PayoutStatus.failed,
    PayoutStatus.cancelled,
    PayoutStatus.reversed,
  }.contains(status);

  bool get isFinal => status == PayoutStatus.paid || returned;
}

class PayoutBalance {
  const PayoutBalance({
    required this.wallet,
    required this.reserved,
    required this.available,
    required this.min,
    required this.max,
  });

  factory PayoutBalance.fromJson(Map<String, dynamic> json) {
    final currency = '${json['currency'] ?? ''}';
    return PayoutBalance(
      wallet: minorOf(json['wallet_minor'], currency),
      reserved: minorOf(json['reserved_minor'], currency),
      available: minorOf(json['available_minor'], currency),
      min: minorOf(json['min_minor'], currency),
      max: minorOf(json['max_minor'], currency),
    );
  }

  final Money wallet;
  final Money reserved;
  final Money available;
  final Money min;
  final Money max;
}

class PayoutList {
  const PayoutList({required this.page, required this.balance});

  final PagedList<PayoutRequest> page;
  final PayoutBalance? balance;
}
