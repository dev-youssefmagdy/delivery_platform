import '../entities/job_entities.dart';

/// Everything the driver does with requests, offers and jobs. Every write
/// takes the Idempotency-Key of its user intent (kept by the caller across
/// retries).
abstract interface class JobRepository {
  Future<RequestFeed> availableRequests();

  Future<List<MyOffer>> myOffers();

  Future<MyOffer> acceptCustomerFare(
    String bookingId, {
    required String idempotencyKey,
    int? etaMinutes,
  });

  Future<MyOffer> counterOffer(
    String bookingId, {
    required int amountMinor,
    required String idempotencyKey,
    String? currency,
    int? etaMinutes,
  });

  Future<void> withdrawOffer(String offerId);

  Future<void> skip(String bookingId);

  /// The current job or null (`GET /provider/bookings/active`).
  Future<Job?> activeJob();

  Future<Job> job(String bookingId);

  Future<Job> acknowledge(String bookingId, {required String idempotencyKey});

  Future<Job> arrived(
    String bookingId, {
    required String idempotencyKey,
    Map<String, Object?> evidence = const {},
  });

  Future<Job> verifyPin(
    String bookingId,
    String pin, {
    required String idempotencyKey,
  });

  Future<Job> start(String bookingId, {required String idempotencyKey});

  Future<Job> complete(String bookingId, {required String idempotencyKey});

  /// Preview of what cancelling costs (no key, nothing written).
  Future<CancellationPreview> previewCancel(
    String bookingId,
    DriverCancelReason reason, {
    String? note,
  });

  Future<CancelOutcome> cancel(
    String bookingId,
    DriverCancelReason reason, {
    required String idempotencyKey,
    String? note,
  });

  Future<void> rateCustomer(
    String bookingId, {
    required int stars,
    required List<CustomerRatingTag> tags,
    String? comment,
    required String idempotencyKey,
  });

  Future<void> report(
    String bookingId, {
    required DriverReportCategory category,
    required String description,
    required String idempotencyKey,
  });
}
