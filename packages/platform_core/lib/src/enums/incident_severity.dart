import 'api_enum.dart';

/// Mirrors backend `IncidentSeverity`; unknown wire values map to [IncidentSeverity.unknown].
enum IncidentSeverity implements ApiEnum {
  low('low'),
  medium('medium'),
  high('high'),
  critical('critical'),
  unknown('');

  const IncidentSeverity(this.value);

  @override
  final String value;

  static IncidentSeverity parse(String? raw) =>
      parseApiEnum(IncidentSeverity.values, raw, IncidentSeverity.unknown);
}
