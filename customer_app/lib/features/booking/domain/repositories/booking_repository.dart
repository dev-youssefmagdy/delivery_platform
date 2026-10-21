import '../entities/booking_entities.dart';
import 'package:latlong2/latlong.dart';

/// A link to follow the trip, shown once by the server.
class ShareLink {
  const ShareLink({required this.url, this.expiresAt});

  final String url;
  final DateTime? expiresAt;
}

/// The server decided a different cancellation fee than the one previewed
/// (409 `cancellation_fee_changed`); [preview] is the fresh decision.
class CancellationFeeChanged implements Exception {
  const CancellationFeeChanged(this.preview);

  final CancellationPreview preview;
}

/// Booking, offers, trip, cancellation, share, rating and report operations
/// of the customer API (docs/API.md 8.11-8.13). Throws `ApiException`.
abstract interface class BookingRepository {
  Future<RouteEstimate> estimate({
    required LatLng origin,
    required LatLng destination,
    required int serviceCategoryId,
    Map<String, Object?> serviceDetails,
  });

  /// Creates the booking; [idempotencyKey] is generated once per user intent.
  Future<Booking> create(BookingRequest request, {required String idempotencyKey});

  Future<Booking> get(String bookingId);

  Future<BookingPage> list({int page = 1, TripFilter filter = const TripFilter()});

  /// The newest in-flight booking, if any.
  Future<BookingSummary?> active();

  Future<List<Offer>> offers(String bookingId);

  Future<Booking> acceptOffer(
    String bookingId,
    String offerId, {
    required String idempotencyKey,
  });

  Future<void> rejectOffer(String bookingId, String offerId);

  /// Changes the request (invalidates active offers on the server).
  Future<Booking> updateRequest(
    String bookingId, {
    required String idempotencyKey,
    int? proposedFareMinor,
    PlaceInfo? pickup,
    PlaceInfo? dropoff,
  });

  /// Cancel with `preview=true`: the fee decision, nothing is committed.
  Future<CancellationPreview> previewCancel(
    String bookingId, {
    required CancelReason reason,
    String? note,
  });

  Future<Booking> cancel(
    String bookingId, {
    required CancelReason reason,
    required String idempotencyKey,
    String? note,
    int? acceptedFeeMinor,
  });

  Future<ShareLink> startShare(String bookingId, {required String idempotencyKey});

  Future<void> stopShare(String bookingId, {required String idempotencyKey});

  Future<void> rate(
    String bookingId, {
    required int stars,
    required List<String> tags,
    required String idempotencyKey,
    String? comment,
  });

  Future<void> report(
    String bookingId, {
    required ReportCategory category,
    required String description,
    required String idempotencyKey,
  });
}
