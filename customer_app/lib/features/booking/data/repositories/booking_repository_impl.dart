import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import '../../domain/entities/booking_entities.dart';
import '../../domain/repositories/booking_repository.dart';
import '../datasources/booking_remote_data_source.dart';
import '../dtos/booking_dto.dart';

class BookingRepositoryImpl implements BookingRepository {
  const BookingRepositoryImpl(this._remote);

  final BookingRemoteDataSource _remote;

  @override
  Future<RouteEstimate> estimate({
    required LatLng origin,
    required LatLng destination,
    required int serviceCategoryId,
    Map<String, Object?> serviceDetails = const {},
  }) async => BookingDto.estimate(
    (await _remote.estimate(
      origin: origin,
      destination: destination,
      serviceCategoryId: serviceCategoryId,
      serviceDetails: serviceDetails,
    )).data,
  );

  @override
  Future<Booking> create(
    BookingRequest request, {
    required String idempotencyKey,
  }) async {
    final body = <String, Object?>{
      'service_category_id': request.serviceCategoryId,
      'booking_type': request.bookingType.value,
      'pickup': BookingDto.placeBody(request.pickup),
      if (request.dropoff != null)
        'dropoff': BookingDto.placeBody(request.dropoff!),
      if (request.stops.isNotEmpty)
        'stops': [for (final s in request.stops) s.toBody()],
      'restricted_goods_policy_version': ?request.restrictedGoodsPolicyVersion,
      'passengers': ?request.passengers,
      'service_details': request.serviceDetails,
      'proposed_fare_minor': request.proposedFareMinor,
      'currency': ?request.currency,
      'payment_method': request.paymentMethod.value,
      if (request.couponCode != null && request.couponCode!.isNotEmpty)
        'coupon_code': request.couponCode,
    };
    return BookingDto.booking(
      (await _remote.create(body, idempotencyKey)).data,
    );
  }

  @override
  Future<Booking> get(String bookingId) async =>
      BookingDto.booking((await _remote.get(bookingId)).data);

  @override
  Future<BookingPage> list({
    int page = 1,
    TripFilter filter = const TripFilter(),
  }) async {
    final result = await _remote.list(page: page, filter: filter);
    return BookingDto.page(result.data, result.meta);
  }

  @override
  Future<BookingSummary?> active() async {
    final result = await _remote.list(
      page: 1,
      perPage: 1,
      filter: const TripFilter(),
      inFlight: true,
    );
    final page = BookingDto.page(result.data, result.meta);
    return page.items.isEmpty ? null : page.items.first;
  }

  @override
  Future<List<Offer>> offers(String bookingId) async =>
      BookingDto.offers((await _remote.offers(bookingId)).data);

  @override
  Future<Booking> acceptOffer(
    String bookingId,
    String offerId, {
    required String idempotencyKey,
  }) async => BookingDto.booking(
    (await _remote.accept(bookingId, offerId, idempotencyKey)).data,
  );

  @override
  Future<void> rejectOffer(String bookingId, String offerId) async {
    await _remote.reject(bookingId, offerId);
  }

  @override
  Future<Booking> updateRequest(
    String bookingId, {
    required String idempotencyKey,
    int? proposedFareMinor,
    PlaceInfo? pickup,
    PlaceInfo? dropoff,
  }) async => BookingDto.booking(
    (await _remote.updateRequest(bookingId, {
      'proposed_fare_minor': ?proposedFareMinor,
      if (pickup != null) 'pickup': BookingDto.placeBody(pickup),
      if (dropoff != null) 'dropoff': BookingDto.placeBody(dropoff),
    }, idempotencyKey)).data,
  );

  @override
  Future<CancellationPreview> previewCancel(
    String bookingId, {
    required CancelReason reason,
    String? note,
  }) async {
    final result = await _remote.cancel(bookingId, {
      'reason_code': reason.value,
      if (note != null && note.isNotEmpty) 'note': note,
      'preview': true,
    });
    return BookingDto.cancellationPreview(result.meta);
  }

  @override
  Future<Booking> cancel(
    String bookingId, {
    required CancelReason reason,
    required String idempotencyKey,
    String? note,
    int? acceptedFeeMinor,
  }) async {
    try {
      return BookingDto.booking(
        (await _remote.cancel(bookingId, {
          'reason_code': reason.value,
          if (note != null && note.isNotEmpty) 'note': note,
          'accepted_fee_minor': ?acceptedFeeMinor,
        }, key: idempotencyKey)).data,
      );
    } on ApiException catch (e) {
      if (e.code == 'cancellation_fee_changed' &&
          e.meta['cancellation'] is Map) {
        throw CancellationFeeChanged(BookingDto.cancellationPreview(e.meta));
      }
      rethrow;
    }
  }

  @override
  Future<ShareLink> startShare(
    String bookingId, {
    required String idempotencyKey,
  }) async {
    final data = (await _remote.startShare(bookingId, idempotencyKey)).data;
    final m = data is Map ? data : const {};
    final url = m['url'];
    if (url is! String || url.isEmpty) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return ShareLink(
      url: url,
      expiresAt: DateTime.tryParse('${m['expires_at']}'),
    );
  }

  @override
  Future<void> stopShare(
    String bookingId, {
    required String idempotencyKey,
  }) async {
    await _remote.stopShare(bookingId, idempotencyKey);
  }

  @override
  Future<void> rate(
    String bookingId, {
    required int stars,
    required List<String> tags,
    required String idempotencyKey,
    String? comment,
  }) async {
    await _remote.rate(bookingId, {
      'stars': stars,
      'tags': tags,
      if (comment != null && comment.trim().isNotEmpty)
        'comment': comment.trim(),
    }, idempotencyKey);
  }

  @override
  Future<void> report(
    String bookingId, {
    required ReportCategory category,
    required String description,
    required String idempotencyKey,
  }) async {
    await _remote.report(bookingId, {
      'category': category.value,
      'description': description,
    }, idempotencyKey);
  }
}
