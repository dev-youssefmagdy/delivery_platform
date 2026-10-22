/// Courier / moving / freight blocks of a booking as BOTH apps read them
/// (docs/API.md P4.1, P4.2): `delivery`, `contacts`, `load`, `*_scope`.
/// Every parser tolerates missing and new fields.
library;

import '../enums/api_enum.dart';
import '../enums/proof_type.dart';
import '../scope/scope_models.dart';

Map<String, dynamic> _map(Object? v) =>
    v is Map ? Map<String, dynamic>.from(v) : const {};

List<Map<String, dynamic>> _maps(Object? v) => [
  if (v is List)
    for (final e in v)
      if (e is Map) Map<String, dynamic>.from(e),
];

int? _int(Object? v) =>
    v is num ? v.toInt() : (v is String ? int.tryParse(v) : null);

double? _double(Object? v) =>
    v is num ? v.toDouble() : (v is String ? double.tryParse(v) : null);

DateTime? _time(Object? v) => v is String ? DateTime.tryParse(v) : null;

/// Where the goods are (`delivery.custody.status`, timeline `custody`).
enum CustodyStatus implements ApiEnum {
  pickedUp('picked_up'),
  inTransit('in_transit'),
  arrivedDropoff('arrived_dropoff'),
  attemptFailed('attempt_failed'),
  returning('returning'),
  delivered('delivered'),
  returned('returned'),
  unknown('');

  const CustodyStatus(this.value);

  @override
  final String value;

  static CustodyStatus parse(String? raw) =>
      parseApiEnum(CustodyStatus.values, raw, CustodyStatus.unknown);

  /// The steps of a normal delivery, in order (the failed / return branch is
  /// shown when it happened).
  static const happyPath = [pickedUp, inTransit, arrivedDropoff, delivered];
}

/// Why a delivery attempt failed (`POST /delivery-attempts` `outcome`).
enum AttemptOutcome implements ApiEnum {
  recipientUnavailable('recipient_unavailable'),
  recipientRefused('recipient_refused'),
  wrongAddress('wrong_address'),
  otpFailed('otp_failed'),
  restrictedItem('restricted_item'),
  other('other'),
  unknown('');

  const AttemptOutcome(this.value);

  @override
  final String value;

  static AttemptOutcome parse(String? raw) =>
      parseApiEnum(AttemptOutcome.values, raw, AttemptOutcome.unknown);

  /// What a driver may choose.
  static const choices = [
    recipientUnavailable,
    recipientRefused,
    wrongAddress,
    otpFailed,
    restrictedItem,
    other,
  ];

  /// A free-text reason is mandatory for these (server rule).
  bool get needsReason => this == restrictedItem || this == other;
}

/// What happens after a failed attempt (`resolve` body).
enum AttemptResolution implements ApiEnum {
  retry('retry'),
  returnToSender('return_to_sender'),
  operations('operations'),
  unknown('');

  const AttemptResolution(this.value);

  @override
  final String value;

  static AttemptResolution parse(String? raw) =>
      parseApiEnum(AttemptResolution.values, raw, AttemptResolution.unknown);
}

class CustodyInfo {
  const CustodyInfo({this.status = CustodyStatus.unknown, this.label});

  final CustodyStatus status;
  final String? label;

  bool get isKnown => status != CustodyStatus.unknown;
}

/// Which proofs the job needs and has, per stage.
class ProofRequirements {
  const ProofRequirements({
    this.pickupRequired = false,
    this.deliveryRequired = const [],
    this.returnRequired = const [],
    this.present = const {},
    this.currentStage,
  });

  factory ProofRequirements.fromJson(Object? json) {
    final m = _map(json);
    List<ProofType> types(Object? v) => [
      if (v is List) for (final e in v) ProofType.parse('$e'),
    ];
    final present = _map(m['present']);
    return ProofRequirements(
      pickupRequired: m['pickup_required'] == true,
      deliveryRequired: types(m['delivery_required']),
      returnRequired: types(m['return_required']),
      present: {for (final e in present.entries) e.key: types(e.value)},
      currentStage: m['current_stage'] as String?,
    );
  }

  final bool pickupRequired;
  final List<ProofType> deliveryRequired;
  final List<ProofType> returnRequired;

  /// stage (`pickup | delivery | return`) -> proof types already recorded.
  final Map<String, List<ProofType>> present;
  final String? currentStage;

  /// Types the current stage still lacks, in the order the server lists them.
  List<ProofType> missingFor(String stage) {
    final needed = switch (stage) {
      'pickup' => pickupRequired ? const [ProofType.photo] : const <ProofType>[],
      'delivery' => deliveryRequired,
      'return' => returnRequired,
      _ => const <ProofType>[],
    };
    final have = present[stage] ?? const [];
    return [
      for (final t in needed)
        if (!have.contains(t)) t,
    ];
  }

  /// Uploadable types the driver can still add in the current stage (the
  /// recipient code and the pickup PIN are never uploaded).
  List<ProofType> get missingUploadable {
    final stage = currentStage;
    if (stage == null) return const [];
    return [
      for (final t in missingFor(stage))
        if (t != ProofType.recipientOtp && t != ProofType.pickupPin) t,
    ];
  }
}

/// State of the recipient code: never the code itself.
class RecipientOtpState {
  const RecipientOtpState({
    this.issued = false,
    this.verified = false,
    this.locked = false,
    this.deliveryStatus,
    this.attemptsRemaining,
    this.attemptsUsed,
    this.maxAttempts,
    this.resendAvailableAt,
    this.resendsLeft,
  });

  static RecipientOtpState? fromJson(Object? json) {
    final m = _map(json);
    if (m.isEmpty || m['required'] != true) return null;
    return RecipientOtpState(
      issued: m['issued'] == true,
      verified: m['verified'] == true,
      locked: m['locked'] == true,
      deliveryStatus: m['delivery_status'] as String?,
      attemptsRemaining: _int(m['attempts_remaining']),
      attemptsUsed: _int(m['attempts_used']),
      maxAttempts: _int(m['max_attempts']),
      resendAvailableAt: _time(m['resend_available_at']),
      resendsLeft: _int(m['resends_left']),
    );
  }

  final bool issued;
  final bool verified;
  final bool locked;

  /// `sent | failed | skipped` (SMS delivery of the code to the recipient).
  final String? deliveryStatus;
  final int? attemptsRemaining;
  final int? attemptsUsed;
  final int? maxAttempts;
  final DateTime? resendAvailableAt;
  final int? resendsLeft;

  bool get smsFailed => deliveryStatus == 'failed';

  /// A resend is possible now: the cooldown is over and resends are left.
  bool canResendAt(DateTime now) =>
      !verified &&
      !locked &&
      (resendsLeft ?? 0) > 0 &&
      (resendAvailableAt == null || !resendAvailableAt!.isAfter(now));
}

/// One failed delivery attempt.
class DeliveryAttempt {
  const DeliveryAttempt({
    required this.id,
    required this.number,
    required this.outcome,
    this.outcomeLabel,
    this.reason,
    this.hasEvidence = false,
    this.resolution,
    this.resolutionLabel,
    this.resolvedAt,
    this.createdAt,
  });

  static DeliveryAttempt? fromJson(Object? json) {
    final m = _map(json);
    if (m['id'] == null) return null;
    return DeliveryAttempt(
      id: '${m['id']}',
      number: _int(m['number']) ?? 1,
      outcome: AttemptOutcome.parse(m['outcome'] as String?),
      outcomeLabel: m['outcome_label'] as String?,
      reason: m['reason'] as String?,
      hasEvidence: m['has_evidence'] == true,
      resolution: m['resolution'] == null
          ? null
          : AttemptResolution.parse(m['resolution'] as String?),
      resolutionLabel: m['resolution_label'] as String?,
      resolvedAt: _time(m['resolved_at']),
      createdAt: _time(m['created_at']),
    );
  }

  final String id;
  final int number;
  final AttemptOutcome outcome;
  final String? outcomeLabel;
  final String? reason;
  final bool hasEvidence;
  final AttemptResolution? resolution;
  final String? resolutionLabel;
  final DateTime? resolvedAt;
  final DateTime? createdAt;

  bool get isPending => resolution == null;
}

class AttemptsState {
  const AttemptsState({
    this.count = 0,
    this.max = 3,
    this.retryAvailable = true,
    this.pending,
    this.items = const [],
  });

  factory AttemptsState.fromJson(Object? json) {
    final m = _map(json);
    return AttemptsState(
      count: _int(m['count']) ?? 0,
      max: _int(m['max']) ?? 3,
      retryAvailable: m['retry_available'] != false,
      pending: DeliveryAttempt.fromJson(m['pending']),
      items: [for (final e in _maps(m['items'])) ?DeliveryAttempt.fromJson(e)],
    );
  }

  final int count;
  final int max;
  final bool retryAvailable;
  final DeliveryAttempt? pending;
  final List<DeliveryAttempt> items;
}

/// The `delivery` block; null for rides.
class DeliveryState {
  const DeliveryState({
    required this.custody,
    required this.proofs,
    required this.attempts,
    this.recipientOtp,
    this.awaitingResolution = false,
    this.restrictedGoodsAcknowledgedAt,
  });

  static DeliveryState? fromJson(Object? json) {
    final m = _map(json);
    if (m.isEmpty) return null;
    final custody = _map(m['custody']);
    return DeliveryState(
      custody: CustodyInfo(
        status: CustodyStatus.parse(custody['status'] as String?),
        label: custody['label'] as String?,
      ),
      proofs: ProofRequirements.fromJson(m['proofs']),
      recipientOtp: RecipientOtpState.fromJson(m['recipient_otp']),
      attempts: AttemptsState.fromJson(m['attempts']),
      awaitingResolution: m['awaiting_resolution'] == true,
      restrictedGoodsAcknowledgedAt: _time(m['restricted_goods_acknowledged_at']),
    );
  }

  final CustodyInfo custody;
  final ProofRequirements proofs;
  final RecipientOtpState? recipientOtp;
  final AttemptsState attempts;
  final bool awaitingResolution;
  final DateTime? restrictedGoodsAcknowledgedAt;
}

/// A contact the customer typed (sender / recipient / freight contacts).
class BookingContact {
  const BookingContact({
    required this.role,
    this.name,
    this.phone,
    this.phoneMasked,
    this.contactMode = 'masked',
  });

  static BookingContact? fromJson(Object? json) {
    final m = _map(json);
    if (m['role'] == null) return null;
    return BookingContact(
      role: '${m['role']}',
      name: m['name'] as String?,
      phone: m['phone'] as String?,
      phoneMasked: m['phone_masked'] as String?,
      contactMode: '${m['contact_mode'] ?? 'masked'}',
    );
  }

  static List<BookingContact> list(Object? json) => [
    for (final e in _maps(json)) ?BookingContact.fromJson(e),
  ];

  final String role;
  final String? name;

  /// Null unless the server exposes the number directly (selected provider,
  /// job in flight). The customer never gets one.
  final String? phone;
  final String? phoneMasked;
  final String contactMode;
}

/// The contact of an intermediate stop.
class StopContact {
  const StopContact({this.name, this.phone, this.phoneMasked, this.note});

  static StopContact? fromJson(Object? json) {
    final m = _map(json);
    if (m.isEmpty) return null;
    return StopContact(
      name: m['name'] as String?,
      phone: m['phone'] as String?,
      phoneMasked: m['phone_masked'] as String?,
      note: m['note'] as String?,
    );
  }

  final String? name;
  final String? phone;
  final String? phoneMasked;
  final String? note;
}

/// A recorded proof (`GET .../proofs`); the picture is behind a signed URL.
class BookingProof {
  const BookingProof({
    required this.id,
    required this.type,
    required this.stage,
    this.typeLabel,
    this.takenAt,
    this.details = const {},
    this.fileUrl,
    this.mime,
  });

  static BookingProof? fromJson(Object? json) {
    final m = _map(json);
    if (m['id'] == null) return null;
    final file = _map(m['file']);
    return BookingProof(
      id: '${m['id']}',
      type: ProofType.parse(m['type'] as String?),
      stage: '${m['stage'] ?? ''}',
      typeLabel: m['type_label'] as String?,
      takenAt: _time(m['taken_at']),
      details: _map(m['details']),
      fileUrl: file['url'] as String?,
      mime: file['mime'] as String?,
    );
  }

  final String id;
  final ProofType type;
  final String stage;
  final String? typeLabel;
  final DateTime? takenAt;
  final Map<String, dynamic> details;
  final String? fileUrl;
  final String? mime;

  bool get hasPicture => fileUrl != null && (mime ?? '').startsWith('image/');
}

class LoadItem {
  const LoadItem({
    required this.code,
    required this.label,
    required this.quantity,
    this.kind,
    this.weightKg,
    this.volumeM3,
    this.length,
    this.width,
    this.height,
    this.note,
  });

  static LoadItem? fromJson(Object? json) {
    final m = _map(json);
    if (m.isEmpty) return null;
    final dims = _map(m['dimensions_cm']);
    return LoadItem(
      code: m['code'] as String?,
      label: '${m['label'] ?? m['code'] ?? ''}',
      quantity: _int(m['quantity']) ?? 1,
      kind: m['kind'] as String?,
      weightKg: _double(m['weight_kg']),
      volumeM3: _double(m['volume_m3']),
      length: _double(dims['length']),
      width: _double(dims['width']),
      height: _double(dims['height']),
      note: m['note'] as String?,
    );
  }

  final String? code;
  final String label;
  final int quantity;
  final String? kind;
  final double? weightKg;
  final double? volumeM3;
  final double? length;
  final double? width;
  final double? height;
  final String? note;

  bool get hasDimensions => length != null && width != null && height != null;
}

/// `load` of a moving / freight booking.
class LoadInfo {
  const LoadInfo({
    this.weightKg,
    this.volumeM3,
    this.declaredValueMinor,
    this.windowStart,
    this.windowEnd,
    this.items = const [],
    this.photos = 0,
    this.videos = 0,
    this.documents = 0,
  });

  static LoadInfo? fromJson(Object? json) {
    final m = _map(json);
    if (m.isEmpty) return null;
    final window = _map(m['pickup_window']);
    final attachments = _map(m['attachments']);
    return LoadInfo(
      weightKg: _double(m['weight_kg']),
      volumeM3: _double(m['volume_m3']),
      declaredValueMinor: _int(m['declared_value_minor']),
      windowStart: _time(window['start']),
      windowEnd: _time(window['end']),
      items: [for (final e in _maps(m['items'])) ?LoadItem.fromJson(e)],
      photos: _int(attachments['photos']) ?? 0,
      videos: _int(attachments['videos']) ?? 0,
      documents: _int(attachments['documents']) ?? 0,
    );
  }

  /// The pre-selection summary of a request card (`weight_kg`, `volume_m3`).
  static LoadInfo? fromCard(Object? load, Object? window) {
    final l = _map(load);
    final w = _map(window);
    if (l.isEmpty && w.isEmpty) return null;
    return LoadInfo(
      weightKg: _double(l['weight_kg']),
      volumeM3: _double(l['volume_m3']),
      windowStart: _time(w['start']),
      windowEnd: _time(w['end']),
    );
  }

  final double? weightKg;
  final double? volumeM3;
  final int? declaredValueMinor;
  final DateTime? windowStart;
  final DateTime? windowEnd;
  final List<LoadItem> items;
  final int photos;
  final int videos;
  final int documents;

  bool get hasFiles => photos + videos + documents > 0;
}

/// An uploaded load file as listed to its owner / the selected provider.
class AttachmentInfo {
  const AttachmentInfo({
    required this.id,
    required this.kind,
    this.mime,
    this.sizeBytes,
    this.url,
    this.field,
  });

  static AttachmentInfo? fromJson(Object? json) {
    final m = _map(json);
    if (m['id'] == null) return null;
    return AttachmentInfo(
      id: '${m['id']}',
      kind: '${m['kind'] ?? 'photo'}',
      mime: m['mime'] as String?,
      sizeBytes: _int(m['size_bytes']),
      url: m['url'] as String?,
      field: m['field'] as String?,
    );
  }

  final String id;

  /// `photo | video | document`.
  final String kind;
  final String? mime;
  final int? sizeBytes;
  final String? url;
  final String? field;

  bool get isImage => (mime ?? '').startsWith('image/');
}

/// The scope blocks of a booking: what was requested and what was agreed,
/// with the server's summary lines (identical in both apps).
class ScopeBlocks {
  const ScopeBlocks({
    this.requested,
    this.requestedSummary = const [],
    this.agreed,
    this.agreedSummary = const [],
  });

  factory ScopeBlocks.fromBooking(Map<String, dynamic> booking) {
    Map<String, Object?>? scope(Object? v) {
      final m = _map(v);
      return m.isEmpty ? null : Map<String, Object?>.from(m);
    }

    return ScopeBlocks(
      requested: scope(booking['requested_scope']),
      requestedSummary: ScopeLine.list(booking['requested_scope_summary']),
      agreed: scope(booking['agreed_scope']),
      agreedSummary: ScopeLine.list(booking['agreed_scope_summary']),
    );
  }

  final Map<String, Object?>? requested;
  final List<ScopeLine> requestedSummary;
  final Map<String, Object?>? agreed;
  final List<ScopeLine> agreedSummary;

  bool get isEmpty => requestedSummary.isEmpty && agreedSummary.isEmpty;
}
