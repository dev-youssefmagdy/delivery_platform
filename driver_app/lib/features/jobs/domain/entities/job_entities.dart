import 'package:platform_core/platform_core.dart';

/// Actions the server allows for the driver right now (`allowed_actions`).
/// Buttons are drawn from this list and never inferred from the status.
enum JobAction implements ApiEnum {
  acknowledge('acknowledge'),
  arrived('arrived'),
  verifyStartPin('verify_start_pin'),
  start('start'),
  complete('complete'),
  cancel('cancel'),
  rateCustomer('rate_customer'),
  report('report'),
  chat('chat'),
  appeal('appeal'),
  unknown('');

  const JobAction(this.value);

  @override
  final String value;

  static JobAction parse(String? raw) =>
      parseApiEnum(JobAction.values, raw, JobAction.unknown);
}

/// Provider cancellation reasons (docs/API.md 8.13).
enum DriverCancelReason implements ApiEnum {
  customerNoShow('customer_no_show'),
  customerUnreachable('customer_unreachable'),
  vehicleProblem('vehicle_problem'),
  emergency('emergency'),
  wrongRequestDetails('wrong_request_details'),
  unsafePickup('unsafe_pickup'),
  other('other'),
  unknown('');

  const DriverCancelReason(this.value);

  @override
  final String value;

  static DriverCancelReason parse(String? raw) =>
      parseApiEnum(DriverCancelReason.values, raw, DriverCancelReason.unknown);

  static const choices = [
    customerNoShow,
    customerUnreachable,
    vehicleProblem,
    emergency,
    wrongRequestDetails,
    unsafePickup,
    other,
  ];
}

/// Report categories the driver can use.
enum DriverReportCategory implements ApiEnum {
  safety('safety'),
  behaviour('behaviour'),
  fareDispute('fare_dispute'),
  routeOrDelay('route_or_delay'),
  other('other');

  const DriverReportCategory(this.value);

  @override
  final String value;
}

/// Tags a driver may attach to a customer rating.
enum CustomerRatingTag implements ApiEnum {
  respectful('respectful', positive: true),
  clearInstructions('clear_instructions', positive: true),
  readyAtPickup('ready_at_pickup', positive: true),
  disrespectful('disrespectful', positive: false),
  notReadyAtPickup('not_ready_at_pickup', positive: false),
  late('late', positive: false);

  const CustomerRatingTag(this.value, {required this.positive});

  @override
  final String value;
  final bool positive;
}

/// Counter-offer limits of one request, from the server.
class CounterBounds {
  const CounterBounds({this.allowed = false, this.minMinor, this.maxMinor});

  final bool allowed;
  final int? minMinor;
  final int? maxMinor;
}

class AreaPoint {
  const AreaPoint({required this.lat, required this.lng, this.name});

  final double lat;
  final double lng;
  final String? name;
}

/// The driver's own offer on a request or in the offers list.
class MyOffer {
  const MyOffer({
    required this.id,
    required this.bookingId,
    required this.status,
    required this.state,
    required this.kind,
    required this.expiresAt,
    this.amount,
    this.version = 1,
    this.revisionCount = 0,
    this.note,
    this.etaSeconds,
    this.distanceMeters,
    this.closedAt,
    this.earningsPreview,
  });

  final String id;
  final String bookingId;
  final OfferStatus status;

  /// pending / selected / rejected / expired / invalidated / withdrawn.
  final OfferState state;
  final OfferKind kind;
  final DateTime expiresAt;
  final Money? amount;
  final int version;
  final int revisionCount;
  final String? note;
  final int? etaSeconds;
  final int? distanceMeters;
  final DateTime? closedAt;

  /// Only when the server says it is available (commission arrives with P3.1).
  final EarningsPreview? earningsPreview;

  bool get isPending => state == OfferState.pending;
}

enum OfferState implements ApiEnum {
  pending('pending'),
  selected('selected'),
  rejected('rejected'),
  expired('expired'),
  invalidated('invalidated'),
  withdrawn('withdrawn'),
  unknown('');

  const OfferState(this.value);

  @override
  final String value;

  static OfferState parse(String? raw) =>
      parseApiEnum(OfferState.values, raw, OfferState.unknown);
}

/// Commission and net amounts exist only once the server computes them.
class EarningsPreview {
  const EarningsPreview({
    required this.grossMinor,
    required this.commissionMinor,
    required this.netMinor,
  });

  final int grossMinor;
  final int commissionMinor;
  final int netMinor;
}

/// A masked request card (before the customer picked anybody).
class RequestCard {
  const RequestCard({
    required this.id,
    required this.service,
    required this.pickup,
    required this.proposedFare,
    required this.counter,
    required this.expiresAt,
    this.dropoff,
    this.distanceToPickupMeters,
    this.tripDistanceMeters,
    this.tripDurationSeconds,
    this.stops = 0,
    this.passengers,
    this.luggageCount,
    this.cargoWeightKg,
    this.scheduledAt,
    this.paymentMethod = PaymentMethodType.cash,
    this.restrictedPickup = false,
    this.myOffer,
  });

  final String id;
  final LocalizedText service;
  final AreaPoint pickup;
  final AreaPoint? dropoff;
  final int? distanceToPickupMeters;
  final Money proposedFare;
  final CounterBounds counter;
  final DateTime? expiresAt;
  final int? tripDistanceMeters;
  final int? tripDurationSeconds;
  final int stops;
  final int? passengers;
  final int? luggageCount;
  final num? cargoWeightKg;
  final DateTime? scheduledAt;
  final PaymentMethodType paymentMethod;
  final bool restrictedPickup;
  final MyOffer? myOffer;
}

/// The request feed answer: cards plus why the list may be empty.
class RequestFeed {
  const RequestFeed({
    this.requests = const [],
    this.eligible = true,
    this.eligibility = const EligibilityInfo(eligible: true),
  });

  final List<RequestCard> requests;
  final bool eligible;
  final EligibilityInfo eligibility;
}

class JobPlace {
  const JobPlace({
    required this.lat,
    required this.lng,
    this.title,
    this.line,
  });

  final double lat;
  final double lng;
  final String? title;
  final String? line;

  String? get label => (title != null && title!.isNotEmpty) ? title : line;
}

class JobCustomer {
  const JobCustomer({
    this.name,
    this.phone,
    this.phoneMasked,
    this.contactMode = 'masked',
    this.ratingAverage,
    this.ratingCount = 0,
  });

  final String? name;

  /// Null unless the server exposes the number directly.
  final String? phone;
  final String? phoneMasked;
  final String contactMode;
  final double? ratingAverage;
  final int ratingCount;
}

class JobTrip {
  const JobTrip({
    this.selectedAt,
    this.headingAt,
    this.arrivedAt,
    this.arrivalVerified,
    this.freeWaitEndsAt,
    this.startedAt,
    this.completedAt,
    this.pinRequired = false,
    this.pinVerified = false,
    this.pinLocked = false,
    this.pinAttemptsRemaining,
    this.supportFlagged = false,
  });

  final DateTime? selectedAt;
  final DateTime? headingAt;
  final DateTime? arrivedAt;
  final bool? arrivalVerified;
  final DateTime? freeWaitEndsAt;
  final DateTime? startedAt;
  final DateTime? completedAt;
  final bool pinRequired;
  final bool pinVerified;
  final bool pinLocked;
  final int? pinAttemptsRemaining;
  final bool supportFlagged;
}

class CancellationFeeRule {
  const CancellationFeeRule({
    required this.stage,
    required this.feeType,
    required this.value,
    this.capMinor,
  });

  final String stage;
  final String feeType;
  final int value;
  final int? capMinor;
}

class CancellationTerms {
  const CancellationTerms({
    this.freeWaitSeconds = 0,
    this.reasons = const [],
    this.providerFees = const [],
    this.redispatch = false,
  });

  final int freeWaitSeconds;
  final List<DriverCancelReason> reasons;
  final List<CancellationFeeRule> providerFees;
  final bool redispatch;
}

/// The server's cancellation decision (preview and commit).
class CancellationPreview {
  const CancellationPreview({
    this.fee,
    this.free = true,
    this.message,
    this.stage,
    this.basis,
    this.recipient,
  });

  final Money? fee;
  final bool free;
  final String? message;
  final String? stage;
  final String? basis;
  final String? recipient;
}

/// What a cancel call did: the booking back to searching (re-dispatch) or
/// ended, plus the decision.
class CancelOutcome {
  const CancelOutcome({
    required this.committed,
    required this.redispatched,
    this.decision,
    this.job,
  });

  final bool committed;
  final bool redispatched;
  final CancellationPreview? decision;
  final Job? job;
}

/// The booking as the selected driver sees it.
class Job {
  const Job({
    required this.id,
    required this.code,
    required this.status,
    required this.service,
    required this.pickup,
    required this.stops,
    required this.trip,
    required this.allowedActions,
    required this.customer,
    required this.terms,
    required this.paymentMethod,
    required this.paymentStatus,
    this.statusLabel,
    this.dropoff,
    this.scheduledAt,
    this.passengers,
    this.serviceDetails = const {},
    this.distanceMeters,
    this.durationSeconds,
    this.agreedFare,
    this.finalFare,
    this.earningsPreview,
  });

  final String id;
  final String code;
  final BookingStatus status;
  final String? statusLabel;
  final LocalizedText service;
  final JobPlace pickup;
  final JobPlace? dropoff;
  final List<JobPlace> stops;
  final DateTime? scheduledAt;
  final int? passengers;
  final Map<String, Object?> serviceDetails;
  final int? distanceMeters;
  final int? durationSeconds;
  final Money? agreedFare;
  final Money? finalFare;
  final PaymentMethodType paymentMethod;
  final PaymentStatus paymentStatus;
  final JobTrip trip;
  final List<JobAction> allowedActions;
  final JobCustomer customer;
  final CancellationTerms terms;
  final EarningsPreview? earningsPreview;

  bool can(JobAction action) => allowedActions.contains(action);

  /// Statuses that end the job for the driver.
  bool get isFinished => const {
    BookingStatus.completed,
    BookingStatus.completedPendingPayment,
    BookingStatus.disputed,
    BookingStatus.cancelledByCustomer,
    BookingStatus.cancelledByProvider,
    BookingStatus.cancelledByOperations,
    BookingStatus.expiredNoProvider,
    BookingStatus.deliveryAttemptFailed,
  }.contains(status);

  bool get isCompleted =>
      status == BookingStatus.completed ||
      status == BookingStatus.completedPendingPayment;

  /// Where the driver should navigate next: the pickup until the trip starts,
  /// then the drop-off (or last stop).
  JobPlace? get navigationTarget {
    if (status == BookingStatus.inProgress) {
      return dropoff ?? (stops.isEmpty ? null : stops.last);
    }
    return pickup;
  }

  /// Cash the driver collects at the end.
  bool get collectsCash => paymentMethod == PaymentMethodType.cash;
}

/// One row of the job history (`GET /provider/bookings`): enough to list the
/// job; the detail screen loads the rest.
class JobHistoryEntry {
  const JobHistoryEntry({
    required this.bookingId,
    required this.code,
    required this.status,
    required this.service,
    required this.when,
    this.amount,
  });

  final String bookingId;
  final String code;
  final BookingStatus status;
  final LocalizedText service;
  final Money? amount;

  /// Completion, cancellation or creation time, whichever applies.
  final DateTime? when;
}

/// A page of the job history.
class JobHistoryPage {
  const JobHistoryPage({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<JobHistoryEntry> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}
