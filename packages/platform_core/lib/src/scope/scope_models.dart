/// Structured scope of a moving / freight offer (SRS 7.4, docs/API.md P4.2).
///
/// The server owns the JSON Schema (`config/offer_scope.php`, exposed by
/// `GET /provider/offer-scope`) and the human readable summaries
/// (`*_scope_summary`). The client never builds a summary: it only edits an
/// [OfferScope] against the template and shows the server's lines.
library;

Map<String, dynamic> _map(Object? v) =>
    v is Map ? Map<String, dynamic>.from(v) : const {};

int? _int(Object? v) =>
    v is num ? v.toInt() : (v is String ? int.tryParse(v) : null);

List<String> _strings(Object? v) => [
  if (v is List)
    for (final e in v)
      if (e != null) '$e',
];

/// One translated line of a server summary (`[{key, label, value}]`).
class ScopeLine {
  const ScopeLine({required this.key, required this.label, required this.value});

  factory ScopeLine.fromJson(Map<String, dynamic> json) => ScopeLine(
    key: '${json['key'] ?? ''}',
    label: '${json['label'] ?? ''}',
    value: '${json['value'] ?? ''}',
  );

  /// Parses a whole `*_scope_summary` list; tolerant of anything else.
  static List<ScopeLine> list(Object? json) => [
    if (json is List)
      for (final e in json)
        if (e is Map) ScopeLine.fromJson(Map<String, dynamic>.from(e)),
  ];

  final String key;
  final String label;
  final String value;
}

/// How a scope line reads at a glance: part of the price, left out, or paid
/// at cost with evidence.
enum ScopeLineStatus { included, excluded, atCost, neutral }

/// Decides the icon of a summary line from the raw scope (the label and value
/// text always come from the server). Unknown keys are neutral.
ScopeLineStatus scopeLineStatus(String key, Map<String, Object?>? scope) {
  if (scope == null || scope.isEmpty) return ScopeLineStatus.neutral;
  ScopeLineStatus treatment(Object? v) => switch (v) {
    'included' => ScopeLineStatus.included,
    'at_cost_with_evidence' => ScopeLineStatus.atCost,
    'excluded' => ScopeLineStatus.excluded,
    _ => ScopeLineStatus.neutral,
  };
  switch (key) {
    case 'tolls':
    case 'parking':
      return treatment(scope[key]);
    case 'helpers':
      return (_int(scope['helpers']) ?? 0) > 0
          ? ScopeLineStatus.included
          : ScopeLineStatus.excluded;
    case 'packing':
      return scope['packing'] == 'none' || scope['packing'] == null
          ? ScopeLineStatus.excluded
          : ScopeLineStatus.included;
    case 'assembly':
      return scope['assembly'] == 'none' || scope['assembly'] == null
          ? ScopeLineStatus.excluded
          : ScopeLineStatus.included;
    case 'stairs':
      return switch (_map(scope['stairs'])['handling']) {
        'included' => ScopeLineStatus.included,
        'extra_per_floor' => ScopeLineStatus.atCost,
        'not_needed' => ScopeLineStatus.neutral,
        _ => ScopeLineStatus.neutral,
      };
    case 'loading_equipment':
      return _strings(scope['loading_equipment']).isEmpty
          ? ScopeLineStatus.excluded
          : ScopeLineStatus.included;
    case 'waiting_allowance':
      return (_int(scope['waiting_allowance_minutes']) ?? 0) > 0
          ? ScopeLineStatus.included
          : ScopeLineStatus.excluded;
    case 'extra_stops':
      return (_int(scope['extra_stops_included']) ?? 0) > 0
          ? ScopeLineStatus.included
          : ScopeLineStatus.excluded;
    case 'exclusions':
      return ScopeLineStatus.excluded;
  }
  return ScopeLineStatus.neutral;
}

/// Options and bounds of the scope builder, read from the server's schema so
/// a new schema version changes the builder without an app release.
class ScopeTemplate {
  const ScopeTemplate({
    required this.version,
    this.vehicleClass,
    this.helpersMax = 10,
    this.floorsMax = 40,
    this.waitingMax = 480,
    this.extraStopsMax = 10,
    this.exclusionsMax = 500,
    this.packingOptions = const ['none', 'wrap_only', 'full_pack'],
    this.stairsOptions = const ['not_needed', 'included', 'extra_per_floor'],
    this.assemblyOptions = const ['none', 'disassembly', 'assembly', 'both'],
    this.equipmentOptions = const [
      'hand_trolley',
      'forklift',
      'crane',
      'tail_lift',
      'straps_blankets',
      'pallet_jack',
    ],
    this.treatmentOptions = const ['included', 'at_cost_with_evidence', 'excluded'],
  });

  /// `data` of `GET /provider/offer-scope`.
  factory ScopeTemplate.fromJson(Object? json) {
    final m = _map(json);
    final props = _map(_map(m['schema'])['properties']);
    List<String> enumOf(Object? prop, List<String> fallback) {
      final values = _strings(_map(prop)['enum']);
      return values.isEmpty ? fallback : values;
    }

    const base = ScopeTemplate(version: 1);
    final stairs = _map(_map(props['stairs'])['properties']);
    final equipment = _map(_map(props['loading_equipment'])['items']);
    int bound(Object? prop, String key, int fallback) =>
        _int(_map(prop)[key]) ?? fallback;
    return ScopeTemplate(
      version: _int(m['version']) ?? 1,
      vehicleClass: m['vehicle_class'] as String?,
      helpersMax: bound(props['helpers'], 'maximum', base.helpersMax),
      floorsMax: bound(stairs['included_floors'], 'maximum', base.floorsMax),
      waitingMax: bound(props['waiting_allowance_minutes'], 'maximum', base.waitingMax),
      extraStopsMax: bound(props['extra_stops_included'], 'maximum', base.extraStopsMax),
      exclusionsMax: bound(props['exclusions'], 'maxLength', base.exclusionsMax),
      packingOptions: enumOf(props['packing'], base.packingOptions),
      stairsOptions: enumOf(stairs['handling'], base.stairsOptions),
      assemblyOptions: enumOf(props['assembly'], base.assemblyOptions),
      equipmentOptions: enumOf(equipment, base.equipmentOptions),
      treatmentOptions: enumOf(props['tolls'], base.treatmentOptions),
    );
  }

  final int version;

  /// Code of the vehicle category the provider offers with; the scope must
  /// name it (`offer_scope_vehicle_mismatch` otherwise).
  final String? vehicleClass;
  final int helpersMax;
  final int floorsMax;
  final int waitingMax;
  final int extraStopsMax;
  final int exclusionsMax;
  final List<String> packingOptions;
  final List<String> stairsOptions;
  final List<String> assemblyOptions;
  final List<String> equipmentOptions;
  final List<String> treatmentOptions;
}

/// Why one field of a scope draft cannot be sent.
enum ScopeIssue { required, outOfRange, invalidChoice, floorsNeeded, tooLong, duplicate }

/// The scope a provider offers (the body `scope` of a counter-offer).
class OfferScope {
  const OfferScope({
    required this.vehicleClass,
    this.helpers = 0,
    this.packing = 'none',
    this.stairsHandling = 'not_needed',
    this.includedFloors = 0,
    this.assembly = 'none',
    this.equipment = const [],
    this.waitingMinutes = 0,
    this.extraStops = 0,
    this.tolls = 'at_cost_with_evidence',
    this.parking = 'at_cost_with_evidence',
    this.exclusions = '',
  });

  /// Reads a stored / requested scope (server canonical form).
  factory OfferScope.fromJson(Map<String, dynamic> json, {String? vehicleClass}) {
    final stairs = _map(json['stairs']);
    return OfferScope(
      vehicleClass: vehicleClass ?? '${json['vehicle_class'] ?? ''}',
      helpers: _int(json['helpers']) ?? 0,
      packing: '${json['packing'] ?? 'none'}',
      stairsHandling: '${stairs['handling'] ?? 'not_needed'}',
      includedFloors: _int(stairs['included_floors']) ?? 0,
      assembly: '${json['assembly'] ?? 'none'}',
      equipment: _strings(json['loading_equipment']),
      waitingMinutes: _int(json['waiting_allowance_minutes']) ?? 0,
      extraStops: _int(json['extra_stops_included']) ?? 0,
      tolls: '${json['tolls'] ?? 'at_cost_with_evidence'}',
      parking: '${json['parking'] ?? 'at_cost_with_evidence'}',
      exclusions: '${json['exclusions'] ?? ''}',
    );
  }

  final String vehicleClass;
  final int helpers;
  final String packing;
  final String stairsHandling;
  final int includedFloors;
  final String assembly;
  final List<String> equipment;
  final int waitingMinutes;
  final int extraStops;
  final String tolls;
  final String parking;
  final String exclusions;

  OfferScope copyWith({
    String? vehicleClass,
    int? helpers,
    String? packing,
    String? stairsHandling,
    int? includedFloors,
    String? assembly,
    List<String>? equipment,
    int? waitingMinutes,
    int? extraStops,
    String? tolls,
    String? parking,
    String? exclusions,
  }) => OfferScope(
    vehicleClass: vehicleClass ?? this.vehicleClass,
    helpers: helpers ?? this.helpers,
    packing: packing ?? this.packing,
    stairsHandling: stairsHandling ?? this.stairsHandling,
    includedFloors: includedFloors ?? this.includedFloors,
    assembly: assembly ?? this.assembly,
    equipment: equipment ?? this.equipment,
    waitingMinutes: waitingMinutes ?? this.waitingMinutes,
    extraStops: extraStops ?? this.extraStops,
    tolls: tolls ?? this.tolls,
    parking: parking ?? this.parking,
    exclusions: exclusions ?? this.exclusions,
  );

  /// The request body `scope`. `included_floors` is 0 when stairs are not
  /// needed (the server forces it too).
  Map<String, Object?> toJson() => {
    'vehicle_class': vehicleClass,
    'helpers': helpers,
    'packing': packing,
    'stairs': {
      'handling': stairsHandling,
      'included_floors': stairsHandling == 'not_needed' ? 0 : includedFloors,
    },
    'assembly': assembly,
    'loading_equipment': equipment,
    'waiting_allowance_minutes': waitingMinutes,
    'extra_stops_included': extraStops,
    'tolls': tolls,
    'parking': parking,
    if (exclusions.trim().isNotEmpty) 'exclusions': exclusions.trim(),
  };

  /// Field issues against [template]; empty when the scope can be sent. Keys
  /// are the schema paths (`helpers`, `stairs.included_floors`, ...), the same
  /// keys the server uses in `meta.scope_errors`.
  Map<String, ScopeIssue> validate(ScopeTemplate template) {
    final issues = <String, ScopeIssue>{};
    if (vehicleClass.trim().isEmpty) issues['vehicle_class'] = ScopeIssue.required;
    if (helpers < 0 || helpers > template.helpersMax) {
      issues['helpers'] = ScopeIssue.outOfRange;
    }
    if (!template.packingOptions.contains(packing)) {
      issues['packing'] = ScopeIssue.invalidChoice;
    }
    if (!template.stairsOptions.contains(stairsHandling)) {
      issues['stairs.handling'] = ScopeIssue.invalidChoice;
    } else if (stairsHandling != 'not_needed') {
      if (includedFloors < 0 || includedFloors > template.floorsMax) {
        issues['stairs.included_floors'] = ScopeIssue.outOfRange;
      } else if (stairsHandling == 'included' && includedFloors < 1) {
        issues['stairs.included_floors'] = ScopeIssue.floorsNeeded;
      }
    }
    if (!template.assemblyOptions.contains(assembly)) {
      issues['assembly'] = ScopeIssue.invalidChoice;
    }
    if (equipment.toSet().length != equipment.length) {
      issues['loading_equipment'] = ScopeIssue.duplicate;
    } else if (equipment.any((e) => !template.equipmentOptions.contains(e)) ||
        equipment.length > template.equipmentOptions.length) {
      issues['loading_equipment'] = ScopeIssue.invalidChoice;
    }
    if (waitingMinutes < 0 || waitingMinutes > template.waitingMax) {
      issues['waiting_allowance_minutes'] = ScopeIssue.outOfRange;
    }
    if (extraStops < 0 || extraStops > template.extraStopsMax) {
      issues['extra_stops_included'] = ScopeIssue.outOfRange;
    }
    if (!template.treatmentOptions.contains(tolls)) {
      issues['tolls'] = ScopeIssue.invalidChoice;
    }
    if (!template.treatmentOptions.contains(parking)) {
      issues['parking'] = ScopeIssue.invalidChoice;
    }
    if (exclusions.trim().runes.length > template.exclusionsMax) {
      issues['exclusions'] = ScopeIssue.tooLong;
    }
    return issues;
  }
}

/// Maps the server's `meta.scope_errors` (`path => keyword`) to field issues,
/// so a refusal lights up the same fields the client check would.
Map<String, ScopeIssue> scopeIssuesFromServer(Object? scopeErrors) {
  final result = <String, ScopeIssue>{};
  final errors = _map(scopeErrors);
  for (final e in errors.entries) {
    final keyword = '${e.value}';
    result[e.key] = switch (keyword) {
      'required' => ScopeIssue.required,
      'minimum' || 'maximum' => ScopeIssue.outOfRange,
      'maxLength' => ScopeIssue.tooLong,
      'uniqueItems' => ScopeIssue.duplicate,
      _ => ScopeIssue.invalidChoice,
    };
  }
  return result;
}
