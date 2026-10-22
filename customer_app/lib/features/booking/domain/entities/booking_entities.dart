import 'package:platform_core/platform_core.dart';

/// Actions the server allows right now (`allowed_actions`). The UI renders
/// buttons from this list and never infers them from the status.
enum BookingAction implements ApiEnum {
  updateRequest('update_request'),
  selectOffer('select_offer'),
  cancel('cancel'),
  share('share'),
  rate('rate'),
  report('report'),
  chat('chat'),
  appeal('appeal'),
  pay('pay'),
  tip('tip'),
  requestAdjustment('request_adjustment'),
  viewReceipt('view_receipt'),
  viewProofs('view_proofs'),
  resolveDeliveryAttempt('resolve_delivery_attempt'),
  unknown('');

  const BookingAction(this.value);

  @override
  final String value;

  static BookingAction parse(String? raw) =>
      parseApiEnum(BookingAction.values, raw, BookingAction.unknown);
}

/// Customer cancellation reasons (docs/API.md 8.13).
enum CancelReason implements ApiEnum {
  changedMind('changed_mind'),
  wrongPickup('wrong_pickup'),
  foundAnotherRide('found_another_ride'),
  driverTooFar('driver_too_far'),
  driverAskedToCancel('driver_asked_to_cancel'),
  waitedTooLong('waited_too_long'),
  other('other'),
  unknown('');

  const CancelReason(this.value);

  @override
  final String value;

  static CancelReason parse(String? raw) =>
      parseApiEnum(CancelReason.values, raw, CancelReason.unknown);

  /// The reasons a customer can choose from.
  static const choices = [
    changedMind,
    wrongPickup,
    foundAnotherRide,
    driverTooFar,
    driverAskedToCancel,
    waitedTooLong,
    other,
  ];
}

/// Report categories (docs/API.md 8.13).
enum ReportCategory implements ApiEnum {
  safety('safety'),
  behaviour('behaviour'),
  fareDispute('fare_dispute'),
  routeOrDelay('route_or_delay'),
  lostItem('lost_item'),
  other('other');

  const ReportCategory(this.value);

  @override
  final String value;
}

/// Rating tags the customer may attach to a provider rating.
enum ProviderRatingTag implements ApiEnum {
  professional('professional', positive: true),
  cleanVehicle('clean_vehicle', positive: true),
  safeDriving('safe_driving', positive: true),
  goodCommunication('good_communication', positive: true),
  onTime('on_time', positive: true),
  carefulWithItems('careful_with_items', positive: true),
  late('late', positive: false),
  unsafeDriving('unsafe_driving', positive: false),
  rude('rude', positive: false),
  dirtyVehicle('dirty_vehicle', positive: false);

  const ProviderRatingTag(this.value, {required this.positive});

  @override
  final String value;
  final bool positive;
}

/// Ordering of the offer list.
enum OfferSort { price, eta, rating }

class PlaceInfo {
  const PlaceInfo({
    required this.lat,
    required this.lng,
    this.title,
    this.line,
    this.contact,
  });

  final double lat;
  final double lng;
  final String? title;
  final String? line;

  /// Contact of an intermediate stop (name, masked phone, note); the phone
  /// itself never reaches the customer app.
  final StopContact? contact;

  String? get label => (title != null && title!.isNotEmpty) ? title : line;
}

class PriceFactor {
  const PriceFactor({
    required this.code,
    required this.label,
    required this.amountMinor,
    this.quantity,
    this.unit,
  });

  final String code;
  final String label;
  final int amountMinor;
  final num? quantity;
  final String? unit;
}

/// Reference estimate shown as guidance only (the customer sets the fare).
class PriceGuidance {
  const PriceGuidance({
    required this.currency,
    required this.lowMinor,
    required this.recommendedMinor,
    required this.highMinor,
    required this.factors,
    this.notice,
  });

  final String currency;
  final int lowMinor;
  final int recommendedMinor;
  final int highMinor;
  final List<PriceFactor> factors;
  final String? notice;
}

/// `GET /routes/estimate` with a category: route size plus price guidance.
class RouteEstimate {
  const RouteEstimate({
    required this.distanceMeters,
    required this.durationSeconds,
    required this.polyline,
    this.guidance,
    this.guidanceUnavailableReason,
  });

  final int distanceMeters;
  final int durationSeconds;
  final String polyline;
  final PriceGuidance? guidance;
  final String? guidanceUnavailableReason;
}

class ProviderCard {
  const ProviderCard({
    required this.id,
    this.displayName,
    this.photoUrl,
    this.ratingAverage = 0,
    this.ratingCount = 0,
    this.ratingProvisional = true,
    this.completedJobs = 0,
  });

  final String id;
  final String? displayName;
  final String? photoUrl;
  final double ratingAverage;
  final int ratingCount;
  final bool ratingProvisional;
  final int completedJobs;
}

class VehicleCard {
  const VehicleCard({
    this.category = const LocalizedText({}),
    this.make,
    this.model,
    this.year,
    this.color,
    this.plate,
    this.passengerCapacity,
  });

  final LocalizedText category;
  final String? make;
  final String? model;
  final int? year;
  final String? color;
  final String? plate;
  final int? passengerCapacity;

  String get title => [make, model].whereType<String>().join(' ');
}

class Offer {
  const Offer({
    required this.id,
    required this.status,
    required this.kind,
    required this.expiresAt,
    this.amount,
    this.version = 1,
    this.revisionCount = 0,
    this.note,
    this.etaSeconds,
    this.distanceMeters,
    this.provider,
    this.vehicle,
    this.scope,
    this.scopeSummary = const [],
  });

  final String id;
  final OfferStatus status;
  final OfferKind kind;
  final DateTime expiresAt;
  final Money? amount;
  final int version;
  final int revisionCount;
  final String? note;
  final int? etaSeconds;
  final int? distanceMeters;
  final ProviderCard? provider;
  final VehicleCard? vehicle;

  /// Moving / freight: what the offer includes, with the server's summary
  /// lines (never built on the device).
  final Map<String, Object?>? scope;
  final List<ScopeLine> scopeSummary;

  bool get isCounterOffer => kind == OfferKind.counterOffer;
}

class TripProgress {
  const TripProgress({
    this.selectedAt,
    this.headingAt,
    this.arrivedAt,
    this.freeWaitEndsAt,
    this.startedAt,
    this.completedAt,
    this.pinRequired = false,
    this.pinVerified = false,
    this.pinLocked = false,
    this.providerLocationVisible = false,
    this.supportFlagged = false,
    this.ratingWindowEndsAt,
  });

  final DateTime? selectedAt;
  final DateTime? headingAt;
  final DateTime? arrivedAt;
  final DateTime? freeWaitEndsAt;
  final DateTime? startedAt;
  final DateTime? completedAt;
  final bool pinRequired;
  final bool pinVerified;
  final bool pinLocked;
  final bool providerLocationVisible;
  final bool supportFlagged;
  final DateTime? ratingWindowEndsAt;
}

class CancellationInfo {
  const CancellationInfo({
    this.by,
    this.reasonCode,
    this.fee,
    this.basis,
    this.cancelledAt,
  });

  final String? by;
  final String? reasonCode;
  final Money? fee;
  final String? basis;
  final DateTime? cancelledAt;
}

class Receipt {
  const Receipt({
    required this.bookingCode,
    required this.paymentMethod,
    required this.paymentStatus,
    this.agreedFare,
    this.finalFare,
    this.startedAt,
    this.completedAt,
    this.durationSeconds,
    this.plannedDistanceMeters,
    this.couponApplied = false,
    this.discount,
    this.customerPayable,
  });

  final String bookingCode;
  final bool couponApplied;
  final Money? discount;

  /// What the customer pays after any coupon (server computed).
  final Money? customerPayable;
  final Money? agreedFare;
  final Money? finalFare;
  final PaymentMethodType paymentMethod;
  final PaymentStatus paymentStatus;
  final DateTime? startedAt;
  final DateTime? completedAt;
  final int? durationSeconds;
  final int? plannedDistanceMeters;
}

class TimelineEntry {
  const TimelineEntry({
    required this.status,
    required this.isNote,
    required this.at,
    required this.sequence,
    this.reason,
    this.custody,
  });

  final BookingStatus status;
  final bool isNote;
  final DateTime? at;
  final int sequence;
  final String? reason;

  /// The custody step of a parcel note (`picked_up` ... `returned`).
  final CustodyStatus? custody;
}

/// One row of the history list.
class BookingSummary {
  const BookingSummary({
    required this.id,
    required this.code,
    required this.status,
    required this.serviceName,
    required this.proposedFare,
    required this.createdAt,
    this.statusLabel,
  });

  final String id;
  final String code;
  final BookingStatus status;
  final String? statusLabel;
  final LocalizedText serviceName;
  final Money proposedFare;
  final DateTime? createdAt;
}

/// The customer's view of a booking (snapshot from the server).
class Booking {
  const Booking({
    required this.id,
    required this.code,
    required this.status,
    required this.serviceId,
    required this.serviceName,
    required this.proposedFare,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.pickup,
    required this.stops,
    required this.trip,
    required this.allowedActions,
    required this.timeline,
    this.statusLabel,
    this.createdAt,
    this.dropoff,
    this.scheduledAt,
    this.guidance,
    this.polyline,
    this.distanceMeters,
    this.durationSeconds,
    this.searchExpiresAt,
    this.requestVersion = 1,
    this.activeOfferCount = 0,
    this.bestOfferMinor,
    this.provider,
    this.vehicle,
    this.agreedFare,
    this.tripPin,
    this.cancellation,
    this.finalFare,
    this.receipt,
    this.bookingType = BookingType.unknown,
    this.contacts = const [],
    this.delivery,
    this.load,
    this.scope = const ScopeBlocks(),
  });

  final String id;
  final String code;
  final BookingStatus status;
  final String? statusLabel;
  final int serviceId;
  final LocalizedText serviceName;
  final Money proposedFare;
  final PaymentMethodType paymentMethod;
  final PaymentStatus paymentStatus;
  final DateTime? createdAt;
  final PlaceInfo pickup;
  final PlaceInfo? dropoff;
  final List<PlaceInfo> stops;
  final DateTime? scheduledAt;
  final PriceGuidance? guidance;
  final String? polyline;
  final int? distanceMeters;
  final int? durationSeconds;
  final DateTime? searchExpiresAt;
  final int requestVersion;
  final int activeOfferCount;
  final int? bestOfferMinor;
  final ProviderCard? provider;
  final VehicleCard? vehicle;
  final Money? agreedFare;
  final String? tripPin;
  final TripProgress trip;
  final CancellationInfo? cancellation;
  final Money? finalFare;
  final Receipt? receipt;
  final List<BookingAction> allowedActions;
  final List<TimelineEntry> timeline;
  final BookingType bookingType;

  /// Sender / recipient / freight contacts (phones masked).
  final List<BookingContact> contacts;

  /// Custody, proofs, recipient code and failed attempts; null for rides.
  final DeliveryState? delivery;

  /// Items, weight, volume, pickup window (moving / freight).
  final LoadInfo? load;

  /// Requested and agreed scope with the server's summaries.
  final ScopeBlocks scope;

  bool can(BookingAction action) => allowedActions.contains(action);

  /// Highest event sequence in the snapshot, used to resync gap detection.
  int get sequence {
    var max = 0;
    for (final e in timeline) {
      if (e.sequence > max) max = e.sequence;
    }
    return max;
  }

  bool get isSearching =>
      status == BookingStatus.searchingOffers ||
      status == BookingStatus.offersReceived;

  bool get isAssigned => const {
    BookingStatus.providerSelected,
    BookingStatus.providerHeadingToPickup,
    BookingStatus.providerArrived,
    BookingStatus.inProgress,
    BookingStatus.deliveryAttemptFailed,
  }.contains(status);

  bool get isFinished => const {
    BookingStatus.completed,
    BookingStatus.completedPendingPayment,
    BookingStatus.disputed,
  }.contains(status);

  bool get isCancelled => const {
    BookingStatus.cancelledByCustomer,
    BookingStatus.cancelledByProvider,
    BookingStatus.cancelledByOperations,
  }.contains(status);

  /// The booking still occupies the customer (banner on Home).
  bool get isInFlight => isSearching || isAssigned;
}

/// Server fee decision returned by `cancel` with `preview=true`.
class CancellationPreview {
  const CancellationPreview({
    required this.fee,
    required this.free,
    this.message,
    this.stageLabel,
  });

  final Money fee;
  final bool free;
  final String? message;
  final String? stageLabel;
}

class BookingPage {
  const BookingPage({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<BookingSummary> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}

/// History filters of My Trips.
class TripFilter {
  const TripFilter({this.serviceCategoryId, this.statuses = const [], this.from, this.to});

  final int? serviceCategoryId;
  final List<BookingStatus> statuses;
  final DateTime? from;
  final DateTime? to;

  bool get isEmpty =>
      serviceCategoryId == null &&
      statuses.isEmpty &&
      from == null &&
      to == null;

  @override
  bool operator ==(Object other) =>
      other is TripFilter &&
      other.serviceCategoryId == serviceCategoryId &&
      other.from == from &&
      other.to == to &&
      other.statuses.length == statuses.length &&
      other.statuses.every(statuses.contains);

  @override
  int get hashCode => Object.hash(serviceCategoryId, from, to, statuses.length);
}

/// Everything the booking form sends; the server owns validation and totals.
class BookingRequest {
  const BookingRequest({
    required this.serviceCategoryId,
    required this.pickup,
    required this.proposedFareMinor,
    this.dropoff,
    this.stops = const [],
    this.serviceDetails = const {},
    this.passengers,
    this.currency,
    this.paymentMethod = PaymentMethodType.cash,
    this.couponCode,
    this.bookingType = BookingType.onDemand,
    this.restrictedGoodsPolicyVersion,
  });

  /// `parcel`, `freight` or `on_demand`, from the category's allowed types.
  final BookingType bookingType;

  /// Id of the current restricted-goods policy the customer acknowledged
  /// (courier only).
  final String? restrictedGoodsPolicyVersion;
  final String? couponCode;
  final int serviceCategoryId;
  final PlaceInfo pickup;
  final PlaceInfo? dropoff;
  final List<StopInput> stops;
  final Map<String, Object?> serviceDetails;
  final int? passengers;
  final int proposedFareMinor;
  final String? currency;
  final PaymentMethodType paymentMethod;
}

/// An intermediate stop with the person to meet there (moving / freight).
/// The contact is stored encrypted and shown to the selected provider only.
class StopInput {
  const StopInput({required this.place, this.contactName, this.contactPhone, this.contactNote});

  final PlaceInfo place;
  final String? contactName;
  final String? contactPhone;
  final String? contactNote;

  bool get hasContact => (contactName ?? '').trim().isNotEmpty || (contactPhone ?? '').trim().isNotEmpty;
}
