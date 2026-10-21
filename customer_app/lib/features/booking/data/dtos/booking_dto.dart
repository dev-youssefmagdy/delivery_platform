import 'package:platform_core/platform_core.dart';

import '../../domain/entities/booking_entities.dart';

/// Wire (JSON) to entity mapping for the customer booking API. Tolerant of
/// missing or new fields (docs/API.md 1: ignore unknown fields).
abstract final class BookingDto {
  static Map<String, dynamic> _map(Object? v) =>
      v is Map ? Map<String, dynamic>.from(v) : const {};

  static List<Map<String, dynamic>> _list(Object? v) => [
    if (v is List)
      for (final e in v)
        if (e is Map) Map<String, dynamic>.from(e),
  ];

  static DateTime? _time(Object? v) =>
      v is String ? DateTime.tryParse(v) : null;

  static int? _int(Object? v) => (v as num?)?.toInt();

  static double? _double(Object? v) =>
      v is num ? v.toDouble() : (v is String ? double.tryParse(v) : null);

  static Money? money(Object? v) {
    if (v is! Map || v['amount_minor'] == null || v['currency'] == null) {
      return null;
    }
    return Money.fromJson(Map<String, dynamic>.from(v));
  }

  static PlaceInfo? place(Object? v) {
    final m = _map(v);
    final lat = _double(m['lat']);
    final lng = _double(m['lng']);
    if (lat == null || lng == null) return null;
    final address = _map(m['address']);
    return PlaceInfo(
      lat: lat,
      lng: lng,
      title: address['title'] as String?,
      line: address['line'] as String?,
    );
  }

  static PriceGuidance? guidance(Object? v) {
    final m = _map(v);
    if (m.isEmpty || m['available'] == false) return null;
    final low = _int(m['low_minor']);
    final rec = _int(m['recommended_minor']);
    final high = _int(m['high_minor']);
    if (low == null || rec == null || high == null) return null;
    return PriceGuidance(
      currency: '${m['currency'] ?? ''}',
      lowMinor: low,
      recommendedMinor: rec,
      highMinor: high,
      notice: m['notice'] as String?,
      factors: [
        for (final f in _list(m['factors']))
          PriceFactor(
            code: '${f['code']}',
            label: '${f['label'] ?? f['code']}',
            amountMinor: _int(f['amount_minor']) ?? 0,
            quantity: f['quantity'] as num?,
            unit: f['unit'] as String?,
          ),
      ],
    );
  }

  static RouteEstimate estimate(Object? data) {
    final m = _map(data);
    return RouteEstimate(
      distanceMeters: _int(m['distance_m']) ?? 0,
      durationSeconds: _int(m['duration_s']) ?? 0,
      polyline: '${m['polyline'] ?? ''}',
      guidance: guidance(m['price_guidance']),
      guidanceUnavailableReason: m['price_guidance_unavailable_reason'] as String?,
    );
  }

  static ProviderCard? provider(Object? v) {
    final m = _map(v);
    if (m['id'] == null) return null;
    final rating = _map(m['rating']);
    return ProviderCard(
      id: '${m['id']}',
      displayName: m['display_name'] as String?,
      photoUrl: m['photo_url'] as String?,
      ratingAverage: _double(rating['average']) ?? 0,
      ratingCount: _int(rating['count']) ?? 0,
      ratingProvisional: rating['provisional'] != false,
      completedJobs: _int(m['completed_jobs']) ?? 0,
    );
  }

  static VehicleCard? vehicle(Object? v) {
    final m = _map(v);
    if (m.isEmpty) return null;
    return VehicleCard(
      category: LocalizedText.fromJson(m['category']),
      make: m['make'] as String?,
      model: m['model'] as String?,
      year: _int(m['year']),
      color: m['color'] as String?,
      plate: m['plate'] as String?,
      passengerCapacity: _int(m['passenger_capacity']),
    );
  }

  static Offer? offer(Object? v) {
    final m = _map(v);
    final expires = _time(m['expires_at']);
    if (m['id'] == null || expires == null) return null;
    return Offer(
      id: '${m['id']}',
      status: OfferStatus.parse(m['status'] as String?),
      kind: OfferKind.parse(m['kind'] as String?),
      expiresAt: expires,
      amount: money(m['amount']),
      version: _int(m['version']) ?? 1,
      revisionCount: _int(m['revision_count']) ?? 0,
      note: m['note'] as String?,
      etaSeconds: _int(m['eta_seconds']),
      distanceMeters: _int(m['distance_m']),
      provider: provider(m['provider']),
      vehicle: vehicle(m['vehicle']),
    );
  }

  static List<Offer> offers(Object? data) => [
    if (data is List)
      for (final e in data) ?offer(e),
  ];

  static TripProgress trip(Object? v) {
    final m = _map(v);
    final pin = _map(m['pin']);
    return TripProgress(
      selectedAt: _time(m['selected_at']),
      headingAt: _time(m['heading_at']),
      arrivedAt: _time(m['arrived_at']),
      freeWaitEndsAt: _time(m['free_wait_ends_at']),
      startedAt: _time(m['started_at']),
      completedAt: _time(m['completed_at']),
      pinRequired: pin['required'] == true,
      pinVerified: pin['verified'] == true,
      pinLocked: pin['locked'] == true,
      providerLocationVisible: m['provider_location_visible'] == true,
      supportFlagged: m['support_flagged'] == true,
      ratingWindowEndsAt: _time(m['rating_window_ends_at']),
    );
  }

  static Receipt? receipt(Object? v) {
    final m = _map(v);
    if (m.isEmpty) return null;
    final payment = _map(m['payment']);
    return Receipt(
      bookingCode: '${m['booking_code'] ?? ''}',
      agreedFare: money(m['agreed_fare']),
      finalFare: money(m['final_fare']),
      paymentMethod: PaymentMethodType.parse(payment['method'] as String?),
      paymentStatus: PaymentStatus.parse(payment['status'] as String?),
      startedAt: _time(m['started_at']),
      completedAt: _time(m['completed_at']),
      durationSeconds: _int(m['duration_s']),
      plannedDistanceMeters: _int(m['planned_distance_m']),
    );
  }

  static CancellationInfo? cancellation(Object? v) {
    final m = _map(v);
    if (m.isEmpty) return null;
    return CancellationInfo(
      by: m['by'] as String?,
      reasonCode: m['reason_code'] as String?,
      fee: money(m['fee']),
      basis: m['basis'] as String?,
      cancelledAt: _time(m['cancelled_at']),
    );
  }

  static BookingSummary summary(Map<String, dynamic> m) {
    final service = _map(m['service']);
    return BookingSummary(
      id: '${m['id']}',
      code: '${m['code'] ?? ''}',
      status: BookingStatus.parse(m['status'] as String?),
      statusLabel: m['status_label'] as String?,
      serviceName: LocalizedText.fromJson(service['name']),
      proposedFare:
          money(m['proposed_fare']) ?? const Money(minor: 0, currency: 'EGP'),
      createdAt: _time(m['created_at']),
    );
  }

  static BookingPage page(Object? data, Map<String, dynamic> meta) {
    final pagination = _map(meta['pagination']);
    return BookingPage(
      items: [for (final e in _list(data)) summary(e)],
      currentPage: _int(pagination['current_page']) ?? 1,
      lastPage: _int(pagination['last_page']) ?? 1,
    );
  }

  static Booking booking(Object? data) {
    final m = _map(data);
    final service = _map(m['service']);
    final payment = _map(m['payment']);
    final route = _map(m['route']);
    final offers = _map(m['offers']);
    final pickup = place(m['pickup']);
    if (m['id'] == null || pickup == null) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return Booking(
      id: '${m['id']}',
      code: '${m['code'] ?? ''}',
      status: BookingStatus.parse(m['status'] as String?),
      statusLabel: m['status_label'] as String?,
      serviceId: _int(service['id']) ?? 0,
      serviceName: LocalizedText.fromJson(service['name']),
      proposedFare:
          money(m['proposed_fare']) ?? const Money(minor: 0, currency: 'EGP'),
      paymentMethod: PaymentMethodType.parse(payment['method'] as String?),
      paymentStatus: PaymentStatus.parse(payment['status'] as String?),
      createdAt: _time(m['created_at']),
      pickup: pickup,
      dropoff: place(m['dropoff']),
      stops: [for (final s in _list(m['stops'])) ?place(s)],
      scheduledAt: _time(m['scheduled_at']),
      guidance: guidance(m['reference_estimate']),
      polyline: route['polyline'] as String?,
      distanceMeters: _int(route['distance_m']),
      durationSeconds: _int(route['duration_s']),
      searchExpiresAt: _time(m['search_expires_at']),
      requestVersion: _int(m['request_version']) ?? 1,
      activeOfferCount: _int(offers['active_count']) ?? 0,
      bestOfferMinor: _int(offers['best_fare_minor']),
      provider: provider(m['provider']),
      vehicle: vehicle(m['vehicle']),
      agreedFare: money(m['agreed_fare']),
      tripPin: m['trip_pin'] as String?,
      trip: trip(m['trip']),
      cancellation: cancellation(m['cancellation']),
      finalFare: money(m['final_fare']),
      receipt: receipt(m['receipt']),
      allowedActions: [
        for (final a in (m['allowed_actions'] as List?) ?? const [])
          BookingAction.parse('$a'),
      ],
      timeline: [
        for (final e in _list(m['timeline']))
          TimelineEntry(
            status: BookingStatus.parse(e['status'] as String?),
            isNote: e['type'] == 'note',
            at: _time(e['at']),
            sequence: _int(e['sequence']) ?? 0,
            reason: e['reason'] as String?,
          ),
      ],
    );
  }

  static CancellationPreview cancellationPreview(Map<String, dynamic> meta) {
    final c = _map(meta['cancellation']);
    return CancellationPreview(
      fee:
          money(c['fee']) ?? const Money(minor: 0, currency: 'EGP'),
      free: c['free'] == true,
      message: c['message'] as String?,
      stageLabel: c['stage_label'] as String?,
    );
  }

  static Map<String, Object?> placeBody(PlaceInfo p) => {
    'lat': p.lat,
    'lng': p.lng,
    if (p.title != null || p.line != null)
      'address': {'title': ?p.title, 'line': ?p.line},
  };
}
