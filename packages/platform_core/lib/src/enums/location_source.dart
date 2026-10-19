import 'api_enum.dart';

/// Mirrors backend `LocationSource`; unknown wire values map to [LocationSource.unknown].
enum LocationSource implements ApiEnum {
  gps('gps'),
  network('network'),
  manual('manual'),
  unknown('');

  const LocationSource(this.value);

  @override
  final String value;

  static LocationSource parse(String? raw) =>
      parseApiEnum(LocationSource.values, raw, LocationSource.unknown);
}
