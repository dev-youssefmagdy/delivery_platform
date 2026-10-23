/// Models of the safety flows (docs/API.md "Safety"). Plain Dart, parsed defensively: an unknown value is shown as received
/// and never crashes a screen that a person may be using in an emergency.
library;

int _int(Object? v, [int fallback = 0]) =>
    v is num ? v.toInt() : int.tryParse('$v') ?? fallback;
DateTime? _date(Object? v) =>
    v is String ? DateTime.tryParse(v)?.toLocal() : null;
String? _str(Object? v) => v is String && v.isNotEmpty ? v : null;
Map<String, dynamic> _map(Object? v) =>
    v is Map ? Map<String, dynamic>.from(v) : <String, dynamic>{};
List<Map<String, dynamic>> _list(Object? v) => v is List
    ? [
        for (final e in v)
          if (e is Map) Map<String, dynamic>.from(e),
      ]
    : const [];

/// Incident categories the apps offer (backend `IncidentCategory`); the label comes from the app's ARB.
const incidentCategories = <String>[
  'emergency',
  'accident',
  'medical',
  'threat',
  'harassment',
  'unsafe_driving',
  'theft',
  'identity_mismatch',
  'other',
];

/// Relationships offered for a trusted contact (backend `TrustedContactRelationship`).
const contactRelationships = <String>[
  'family',
  'partner',
  'friend',
  'colleague',
  'other',
];

class EmergencyNumber {
  const EmergencyNumber({required this.key, required this.number, this.label});

  /// `police`, `ambulance`, `fire`, `tourist_police` (the app names them in its own language when [label] is null).
  final String key;
  final String number;
  final String? label;

  factory EmergencyNumber.fromJson(Map<String, dynamic> json) =>
      EmergencyNumber(
        key: _str(json['key']) ?? 'other',
        number: _str(json['number']) ?? '',
        label: _str(json['label']),
      );

  Map<String, dynamic> toJson() => {'key': key, 'number': number};
}

/// `safety` block of `GET /config/app`. The platform never dispatches emergency services: [emergencyDispatch] is false.
class SafetyConfig {
  const SafetyConfig({
    this.numbers = const [],
    this.holdSeconds = 2,
    this.contactsMax = 5,
    this.emergencyDispatch = false,
  });

  final List<EmergencyNumber> numbers;
  final int holdSeconds;
  final int contactsMax;
  final bool emergencyDispatch;

  factory SafetyConfig.fromJson(Map<String, dynamic> json) => SafetyConfig(
    numbers: [
      for (final n in _list(json['emergency_numbers']))
        EmergencyNumber.fromJson(n),
    ].where((n) => n.number.isNotEmpty).toList(),
    holdSeconds: _int(json['sos_hold_seconds'], 2).clamp(1, 5),
    contactsMax: _int(json['trusted_contacts_max'], 5),
    emergencyDispatch: json['emergency_dispatch'] == true,
  );

  Map<String, dynamic> toJson() => {
    'emergency_numbers': [for (final n in numbers) n.toJson()],
    'sos_hold_seconds': holdSeconds,
    'trusted_contacts_max': contactsMax,
    'emergency_dispatch': emergencyDispatch,
  };
}

/// One line of "what was done" returned by the server, shown as received.
class SafetyAction {
  const SafetyAction({
    required this.code,
    required this.status,
    required this.message,
  });

  /// `incident_recorded`, `location_captured`, `safety_team`, `trusted_contacts`, `emergency_services`.
  final String code;

  /// `done`, `queued`, `partial`, `none`, `skipped`, `not_available`, `not_contacted`.
  final String status;
  final String message;

  bool get isPositive => status == 'done';

  factory SafetyAction.fromJson(Map<String, dynamic> json) => SafetyAction(
    code: _str(json['code']) ?? '',
    status: _str(json['status']) ?? '',
    message: _str(json['message']) ?? '',
  );
}

class SafetyEvent {
  const SafetyEvent({
    required this.type,
    required this.label,
    required this.actor,
    this.message,
    this.at,
  });

  final String type;
  final String label;

  /// `customer`, `provider`, `staff`, `system`.
  final String actor;
  final String? message;
  final DateTime? at;

  factory SafetyEvent.fromJson(Map<String, dynamic> json) => SafetyEvent(
    type: _str(json['type']) ?? '',
    label: _str(json['label']) ?? '',
    actor: _str(json['actor']) ?? 'system',
    message: _str(json['message']),
    at: _date(json['at']),
  );
}

class ContactActionView {
  const ContactActionView({
    required this.name,
    required this.action,
    required this.status,
    required this.statusLabel,
    this.at,
  });

  final String? name;
  final String action;
  final String status;
  final String statusLabel;
  final DateTime? at;

  factory ContactActionView.fromJson(Map<String, dynamic> json) =>
      ContactActionView(
        name: _str(json['name']),
        action: _str(json['action']) ?? '',
        status: _str(json['status']) ?? '',
        statusLabel: _str(json['status_label']) ?? '',
        at: _date(json['at']),
      );
}

class IncidentFile {
  const IncidentFile({
    required this.id,
    required this.mime,
    required this.sizeBytes,
    required this.url,
  });

  final String id;
  final String mime;
  final int sizeBytes;

  /// Signed link, valid for a few minutes: load it with `ApiClient.getBytes` right away.
  final String url;

  bool get isImage => mime.startsWith('image/');

  factory IncidentFile.fromJson(Map<String, dynamic> json) => IncidentFile(
    id: _str(json['id']) ?? '',
    mime: _str(json['mime']) ?? '',
    sizeBytes: _int(json['size_bytes']),
    url: _str(json['url']) ?? '',
  );
}

class SafetyIncident {
  const SafetyIncident({
    required this.id,
    required this.number,
    required this.trigger,
    required this.category,
    required this.categoryLabel,
    required this.status,
    required this.statusLabel,
    required this.occurredAt,
    required this.canAddUpdate,
    this.bookingId,
    this.bookingCode,
    this.acknowledgedAt,
    this.resolvedAt,
    this.outcomeLabel,
    this.narrative,
    this.timeline = const [],
    this.trustedContacts = const [],
    this.files = const [],
    this.actions = const [],
    this.emergencyNumbers = const [],
    this.emergencyNotice,
    this.joinedExisting = false,
    this.detailed = false,
  });

  final String id;
  final String number;

  /// `sos` or `report`.
  final String trigger;
  final String category;
  final String categoryLabel;

  /// `new`, `acknowledged`, `investigating`, `escalated`, `resolved`.
  final String status;
  final String statusLabel;
  final DateTime occurredAt;
  final bool canAddUpdate;
  final String? bookingId;
  final String? bookingCode;
  final DateTime? acknowledgedAt;
  final DateTime? resolvedAt;
  final String? outcomeLabel;
  final String? narrative;
  final List<SafetyEvent> timeline;
  final List<ContactActionView> trustedContacts;
  final List<IncidentFile> files;
  final List<SafetyAction> actions;
  final List<EmergencyNumber> emergencyNumbers;
  final String? emergencyNotice;

  /// A repeated SOS press joined the case that was already open.
  final bool joinedExisting;

  /// False for list rows (no timeline or files).
  final bool detailed;

  bool get isResolved => status == 'resolved';
  bool get isSos => trigger == 'sos';

  factory SafetyIncident.fromJson(Map<String, dynamic> json) {
    final booking = json['booking'] is Map ? _map(json['booking']) : null;
    return SafetyIncident(
      id: _str(json['id']) ?? '',
      number: _str(json['number']) ?? '',
      trigger: _str(json['trigger']) ?? 'report',
      category: _str(json['category']) ?? 'other',
      categoryLabel:
          _str(json['category_label']) ?? _str(json['category']) ?? '',
      status: _str(json['status']) ?? 'new',
      statusLabel: _str(json['status_label']) ?? _str(json['status']) ?? '',
      occurredAt: _date(json['occurred_at']) ?? DateTime.now(),
      canAddUpdate: json['can_add_update'] == true,
      bookingId: _str(booking?['id']),
      bookingCode: _str(booking?['code']),
      acknowledgedAt: _date(json['acknowledged_at']),
      resolvedAt: _date(json['resolved_at']),
      outcomeLabel: _str(json['outcome_label']),
      narrative: _str(json['narrative']),
      timeline: [
        for (final e in _list(json['timeline'])) SafetyEvent.fromJson(e),
      ],
      trustedContacts: [
        for (final e in _list(json['trusted_contacts']))
          ContactActionView.fromJson(e),
      ],
      files: [
        for (final e in _list(json['attachments'])) IncidentFile.fromJson(e),
      ],
      actions: [
        for (final e in _list(json['actions'])) SafetyAction.fromJson(e),
      ],
      emergencyNumbers: [
        for (final e in _list(json['emergency_numbers']))
          EmergencyNumber.fromJson(e),
      ],
      emergencyNotice: _str(json['emergency_notice']),
      joinedExisting: json['joined_existing'] == true,
      detailed: json.containsKey('timeline'),
    );
  }
}

class IncidentPage {
  const IncidentPage({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<SafetyIncident> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}

class TrustedContact {
  const TrustedContact({
    required this.id,
    required this.name,
    required this.phoneMasked,
    required this.notifyOnSos,
    required this.shareTrip,
    required this.shareLocationOnSos,
    this.relationship,
  });

  final String id;
  final String name;
  final String phoneMasked;
  final String? relationship;
  final bool notifyOnSos;
  final bool shareTrip;
  final bool shareLocationOnSos;

  factory TrustedContact.fromJson(Map<String, dynamic> json) => TrustedContact(
    id: _str(json['id']) ?? '',
    name: _str(json['name']) ?? '',
    phoneMasked: _str(json['phone_masked']) ?? '',
    relationship: _str(json['relationship']),
    notifyOnSos: json['notify_on_sos'] != false,
    shareTrip: json['share_trip'] != false,
    shareLocationOnSos: json['share_location_on_sos'] == true,
  );
}

class TrustedContactList {
  const TrustedContactList({required this.items, required this.max});

  final List<TrustedContact> items;
  final int max;

  bool get isFull => items.length >= max;
}

/// What a person typed for a contact. [consentConfirmed] must be true: the person confirms the contact agreed.
class TrustedContactInput {
  const TrustedContactInput({
    required this.name,
    required this.phone,
    required this.consentConfirmed,
    this.relationship,
    this.notifyOnSos = true,
    this.shareTrip = true,
    this.shareLocationOnSos = false,
  });

  final String name;
  final String phone;
  final String? relationship;
  final bool consentConfirmed;
  final bool notifyOnSos;
  final bool shareTrip;
  final bool shareLocationOnSos;

  Map<String, dynamic> toJson() => {
    'name': name,
    'phone': phone,
    'relationship': ?relationship,
    'consent_confirmed': consentConfirmed,
    'notify_on_sos': notifyOnSos,
    'share_trip': shareTrip,
    'share_location_on_sos': shareLocationOnSos,
  };
}

class TripShareResult {
  const TripShareResult({
    required this.url,
    required this.expiresAt,
    required this.total,
    required this.sent,
    required this.failed,
    required this.skipped,
  });

  final String url;
  final DateTime? expiresAt;
  final int total;
  final int sent;
  final int failed;
  final int skipped;

  factory TripShareResult.fromJson(Map<String, dynamic> json) {
    final c = _map(json['contacts']);
    return TripShareResult(
      url: _str(json['url']) ?? '',
      expiresAt: _date(json['expires_at']),
      total: _int(c['total']),
      sent: _int(c['sent']),
      failed: _int(c['failed']),
      skipped: _int(c['skipped']),
    );
  }
}

/// A GPS position for an alert.
class SafetyFix {
  const SafetyFix({
    required this.latitude,
    required this.longitude,
    this.accuracyMeters,
    this.capturedAt,
  });

  final double latitude;
  final double longitude;
  final double? accuracyMeters;
  final DateTime? capturedAt;
}
