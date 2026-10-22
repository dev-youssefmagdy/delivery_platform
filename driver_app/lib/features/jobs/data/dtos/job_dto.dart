import 'package:platform_core/platform_core.dart';

import '../../domain/entities/job_entities.dart';

/// Wire (JSON) to entity mapping for the driver job API. Tolerant of missing
/// and new fields (docs/API.md 1).
abstract final class JobDto {
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
    final m = _map(v);
    final minor = _int(m['amount_minor']);
    final currency = m['currency'];
    if (minor == null || currency is! String) return null;
    return Money(minor: minor, currency: currency, exponent: _int(m['exponent']) ?? 2);
  }

  static AreaPoint? _area(Object? v) {
    final m = _map(v);
    final lat = _double(m['lat']);
    final lng = _double(m['lng']);
    if (lat == null || lng == null) return null;
    return AreaPoint(lat: lat, lng: lng, name: m['name'] as String?);
  }

  static JobPlace? place(Object? v) {
    final m = _map(v);
    final lat = _double(m['lat']);
    final lng = _double(m['lng']);
    if (lat == null || lng == null) return null;
    final address = _map(m['address']);
    return JobPlace(
      lat: lat,
      lng: lng,
      title: address['title'] as String?,
      line: address['line'] as String?,
      contact: StopContact.fromJson(m['contact']),
    );
  }

  static EarningsPreview? earnings(Object? v) {
    final m = _map(v);
    if (m['available'] != true) return null;
    final gross = _int(m['gross_minor']);
    final commission = _int(m['commission_minor']);
    final net = _int(m['net_minor']);
    if (gross == null || commission == null || net == null) return null;
    return EarningsPreview(
      grossMinor: gross,
      commissionMinor: commission,
      netMinor: net,
    );
  }

  static MyOffer? offer(Object? v) {
    final m = _map(v);
    final expires = _time(m['expires_at']);
    if (m['id'] == null || expires == null) return null;
    return MyOffer(
      id: '${m['id']}',
      bookingId: '${m['booking_id'] ?? ''}',
      status: OfferStatus.parse(m['status'] as String?),
      state: OfferState.parse(m['state'] as String?),
      kind: OfferKind.parse(m['kind'] as String?),
      expiresAt: expires,
      amount: money(m['amount']),
      version: _int(m['version']) ?? 1,
      revisionCount: _int(m['revision_count']) ?? 0,
      note: m['note'] as String?,
      etaSeconds: _int(m['eta_seconds']),
      distanceMeters: _int(m['distance_m']),
      closedAt: _time(m['closed_at']),
      earningsPreview: earnings(m['earnings_preview']),
      scope: m['scope'] is Map ? Map<String, Object?>.from(m['scope'] as Map) : null,
      scopeSummary: ScopeLine.list(m['scope_summary']),
    );
  }

  /// One item of `GET /provider/bookings` (docs/API.md 8.14).
  static JobHistoryEntry? historyEntry(Object? v) {
    final m = _map(v);
    if (m['id'] == null) return null;
    return JobHistoryEntry(
      bookingId: '${m['id']}',
      code: '${m['code'] ?? ''}',
      status: BookingStatus.parse(m['status'] as String?),
      service: LocalizedText.fromJson(_map(m['service'])['name']),
      amount: money(m['final_fare']) ?? money(m['agreed_fare']),
      when:
          _time(m['completed_at']) ??
          _time(m['cancelled_at']) ??
          _time(m['created_at']),
    );
  }

  static JobHistoryPage historyPage(Object? data, Map<String, dynamic> meta) {
    final pagination = _map(meta['pagination']);
    return JobHistoryPage(
      items: [for (final e in _list(data)) ?historyEntry(e)],
      currentPage: _int(pagination['current_page']) ?? 1,
      lastPage: _int(pagination['last_page']) ?? 1,
    );
  }

  static List<MyOffer> offers(Object? data) => [
    for (final e in _list(data)) ?offer(e),
  ];

  static RequestCard? card(Object? v) {
    final m = _map(v);
    final pickup = _area(m['pickup_area']);
    final fare = money(m['proposed_fare']);
    if (m['id'] == null || pickup == null || fare == null) return null;
    final counter = _map(m['counter_offer']);
    final details = _map(m['details']);
    return RequestCard(
      id: '${m['id']}',
      service: LocalizedText.fromJson(_map(m['service'])['name']),
      pickup: pickup,
      dropoff: _area(m['dropoff_area']),
      distanceToPickupMeters: _int(m['distance_to_pickup_m']),
      proposedFare: fare,
      counter: CounterBounds(
        allowed: counter['allowed'] == true,
        minMinor: _int(counter['min_minor']),
        maxMinor: _int(counter['max_minor']),
      ),
      expiresAt: _time(m['expires_at']),
      tripDistanceMeters: _int(details['distance_m']),
      tripDurationSeconds: _int(details['duration_s']),
      stops: _int(details['stops']) ?? 0,
      passengers: _int(details['passengers']),
      luggageCount: _int(details['luggage_count']),
      cargoWeightKg: details['cargo_weight_kg'] as num?,
      scheduledAt: _time(details['scheduled_at']),
      paymentMethod: PaymentMethodType.parse(
        details['payment_method'] as String?,
      ),
      restrictedPickup: details['restricted_pickup'] == true,
      myOffer: offer(m['my_offer']),
      scopeRequired: details['scope_required'] == true,
      load: LoadInfo.fromCard(details['load'], details['pickup_window']),
      requestedScope: details['requested_scope'] is Map && (details['requested_scope'] as Map).isNotEmpty
          ? Map<String, Object?>.from(details['requested_scope'] as Map)
          : null,
      requestedScopeSummary: ScopeLine.list(details['requested_scope_summary']),
    );
  }

  static RequestFeed feed(ApiResult<Object?> result) {
    final eligible = result.meta['eligible'] != false;
    final eligibility = result.meta['eligibility'];
    return RequestFeed(
      requests: [for (final e in _list(result.data)) ?card(e)],
      eligible: eligible,
      eligibility: eligibility is Map
          ? EligibilityInfo.fromJson({
              ...Map<String, dynamic>.from(eligibility),
              'eligible': eligible,
            })
          : EligibilityInfo(eligible: eligible),
    );
  }

  static JobTrip trip(Object? v) {
    final m = _map(v);
    final pin = _map(m['pin']);
    return JobTrip(
      selectedAt: _time(m['selected_at']),
      headingAt: _time(m['heading_at']),
      arrivedAt: _time(m['arrived_at']),
      arrivalVerified: m['arrival_verified'] as bool?,
      freeWaitEndsAt: _time(m['free_wait_ends_at']),
      startedAt: _time(m['started_at']),
      completedAt: _time(m['completed_at']),
      pinRequired: pin['required'] == true,
      pinVerified: pin['verified'] == true,
      pinLocked: pin['locked'] == true,
      pinAttemptsRemaining: _int(pin['attempts_remaining']),
      supportFlagged: m['support_flagged'] == true,
    );
  }

  static CancellationTerms terms(Object? v) {
    final m = _map(v);
    return CancellationTerms(
      freeWaitSeconds: _int(m['free_wait_seconds']) ?? 0,
      reasons: [
        if (m['reasons'] is List)
          for (final r in m['reasons'] as List)
            DriverCancelReason.parse('$r'),
      ],
      providerFees: [
        for (final f in _list(m['provider_fees']))
          CancellationFeeRule(
            stage: '${f['stage']}',
            feeType: '${f['fee_type']}',
            value: _int(f['value']) ?? 0,
            capMinor: _int(f['cap_minor']),
          ),
      ],
      redispatch: m['redispatch'] == true,
    );
  }

  static JobCustomer customer(Object? v) {
    final m = _map(v);
    final rating = _map(m['rating']);
    return JobCustomer(
      name: m['name'] as String?,
      phone: m['phone'] as String?,
      phoneMasked: m['phone_masked'] as String?,
      contactMode: '${m['contact_mode'] ?? 'masked'}',
      ratingAverage: _double(rating['average']),
      ratingCount: _int(rating['count']) ?? 0,
    );
  }

  /// A booking the driver holds. Throws `invalid_response` without a pickup.
  static Job job(Object? data) {
    final m = _map(data);
    final pickup = place(m['pickup']);
    if (m['id'] == null || pickup == null) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    final route = _map(m['route']);
    final payment = _map(m['payment']);
    final details = _map(m['service_details']);
    return Job(
      id: '${m['id']}',
      code: '${m['code'] ?? ''}',
      status: BookingStatus.parse(m['status'] as String?),
      statusLabel: m['status_label'] as String?,
      service: LocalizedText.fromJson(_map(m['service'])['name']),
      pickup: pickup,
      dropoff: place(m['dropoff']),
      stops: [for (final s in _list(m['stops'])) ?place(s)],
      scheduledAt: _time(m['scheduled_at']),
      passengers: _int(m['passengers']),
      serviceDetails: Map<String, Object?>.from(details),
      distanceMeters: _int(route['distance_m']),
      durationSeconds: _int(route['duration_s']),
      agreedFare: money(m['agreed_fare']),
      finalFare: money(m['final_fare']),
      paymentMethod: PaymentMethodType.parse(payment['method'] as String?),
      paymentStatus: PaymentStatus.parse(payment['status'] as String?),
      trip: trip({
        ...Map<String, dynamic>.from(_map(m['trip'])),
        'selected_at': _map(m['trip'])['selected_at'] ?? m['selected_at'],
      }),
      allowedActions: [
        if (m['allowed_actions'] is List)
          for (final a in m['allowed_actions'] as List) JobAction.parse('$a'),
      ],
      customer: customer(m['customer']),
      terms: terms(m['cancellation_terms']),
      earningsPreview: earnings(m['earnings_preview']),
      contacts: BookingContact.list(m['contacts']),
      delivery: DeliveryState.fromJson(m['delivery']),
      load: LoadInfo.fromJson(m['load']),
      scope: ScopeBlocks.fromBooking(m),
      bookingType: BookingType.parse(m['booking_type'] as String?),
    );
  }

  static CancellationPreview cancellation(Object? v) {
    final m = _map(v);
    final fee = _map(m['fee']);
    final feeMinor = _int(fee['amount_minor']);
    final currency = fee['currency'];
    return CancellationPreview(
      fee: feeMinor == null || currency is! String
          ? null
          : Money(minor: feeMinor, currency: currency),
      free: m['free'] != false,
      message: m['message'] as String?,
      stage: m['stage'] as String?,
      basis: m['basis'] as String?,
      recipient: m['recipient'] as String?,
    );
  }
}
