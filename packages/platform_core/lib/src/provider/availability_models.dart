import '../enums/offline_reason.dart';
import '../enums/tracking_state.dart';
import 'provider_models.dart';

List<Map<String, dynamic>> _maps(Object? raw) => [
  if (raw is List)
    for (final item in raw)
      if (item is Map) Map<String, dynamic>.from(item),
];

/// How the app must track the position right now, decided by the server.
class TrackingPlan {
  const TrackingPlan({
    required this.state,
    required this.intervalSeconds,
    this.staleAfterSeconds,
  });

  factory TrackingPlan.fromJson(Map<String, dynamic> json) => TrackingPlan(
    state: TrackingState.parse(json['state'] as String?),
    intervalSeconds: (json['interval_seconds'] as num?)?.toInt() ?? 0,
    staleAfterSeconds: (json['stale_after_seconds'] as num?)?.toInt(),
  );

  static const off = TrackingPlan(state: TrackingState.off, intervalSeconds: 0);

  final TrackingState state;

  /// Seconds between position uploads; 0 while tracking is off.
  final int intervalSeconds;

  /// After this long without an upload the server takes the driver offline.
  final int? staleAfterSeconds;

  bool get isTracking => state.isTracking && intervalSeconds > 0;
}

/// `GET /provider/availability` (data.availability) and the answers of
/// going online or offline.
class ProviderAvailability {
  const ProviderAvailability({
    required this.online,
    required this.tracking,
    this.onlineSince,
    this.vehicleId,
    this.lastOfflineReason,
    this.activeBookingId,
    this.channel,
  });

  factory ProviderAvailability.fromJson(Map<String, dynamic> json) {
    final tracking = json['tracking'];
    final realtime = json['realtime'];
    final since = json['online_since'] as String?;
    final reason = json['last_offline_reason'] as String?;
    return ProviderAvailability(
      online: json['online'] == true,
      onlineSince: since == null ? null : DateTime.tryParse(since),
      vehicleId: json['vehicle_id'] as String?,
      lastOfflineReason: reason == null ? null : OfflineReason.parse(reason),
      activeBookingId: json['active_booking_id'] as String?,
      tracking: tracking is Map
          ? TrackingPlan.fromJson(Map<String, dynamic>.from(tracking))
          : TrackingPlan.off,
      channel: realtime is Map ? realtime['channel'] as String? : null,
    );
  }

  final bool online;
  final DateTime? onlineSince;
  final String? vehicleId;
  final OfflineReason? lastOfflineReason;
  final String? activeBookingId;
  final TrackingPlan tracking;

  /// The private channel to listen on (`private-provider.{uuid}`).
  final String? channel;

  /// The channel name without the `private-` prefix, as the realtime client
  /// expects it.
  String? get channelName => channel?.replaceFirst('private-', '');

  bool get hasActiveJob => activeBookingId != null;
}

/// Availability plus why the driver may or may not go online right now.
class AvailabilitySnapshot {
  const AvailabilitySnapshot({
    required this.availability,
    required this.eligibility,
  });

  factory AvailabilitySnapshot.fromJson(Map<String, dynamic> json) =>
      AvailabilitySnapshot(
        availability: ProviderAvailability.fromJson(
          Map<String, dynamic>.from(json['availability'] as Map),
        ),
        eligibility: EligibilityInfo.fromJson(
          Map<String, dynamic>.from(json['eligibility'] as Map),
        ),
      );

  final ProviderAvailability availability;
  final EligibilityInfo eligibility;

  AvailabilitySnapshot copyWith({
    ProviderAvailability? availability,
    EligibilityInfo? eligibility,
  }) => AvailabilitySnapshot(
    availability: availability ?? this.availability,
    eligibility: eligibility ?? this.eligibility,
  );
}

/// `POST /provider/location` answer.
class LocationUploadResult {
  const LocationUploadResult({
    required this.received,
    required this.applied,
    required this.flagged,
    required this.tracking,
  });

  factory LocationUploadResult.fromJson(Map<String, dynamic> json) {
    final tracking = json['tracking'];
    return LocationUploadResult(
      received: (json['received'] as num?)?.toInt() ?? 0,
      applied: (json['applied'] as num?)?.toInt() ?? 0,
      flagged: (json['flagged'] as num?)?.toInt() ?? 0,
      tracking: tracking is Map
          ? TrackingPlan.fromJson(Map<String, dynamic>.from(tracking))
          : TrackingPlan.off,
    );
  }

  final int received;
  final int applied;
  final int flagged;
  final TrackingPlan tracking;
}

/// The typed reasons of a `provider_not_eligible` (422) answer, from
/// `ApiException.meta['reasons']`.
EligibilityInfo eligibilityFromMeta(Map<String, dynamic> meta) =>
    EligibilityInfo(
      eligible: false,
      issues: [
        for (final r in _maps(meta['reasons']))
          EligibilityIssueInfo.fromJson(r),
      ],
    );
