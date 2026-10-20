import 'api_enum.dart';

/// Mirrors backend `LocationPrecision`: how trustworthy a geocoded address is.
/// Only [exact] may be shown as a precise address (SRS §6.3).
enum LocationPrecision implements ApiEnum {
  exact('exact'),
  street('street'),
  approximate('approximate'),
  unknown('');

  const LocationPrecision(this.value);

  @override
  final String value;

  static LocationPrecision parse(String? raw) =>
      parseApiEnum(LocationPrecision.values, raw, LocationPrecision.unknown);
}
