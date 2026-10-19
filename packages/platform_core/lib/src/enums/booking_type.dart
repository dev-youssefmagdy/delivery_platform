import 'api_enum.dart';

/// Mirrors backend `BookingType`; unknown wire values map to [BookingType.unknown].
enum BookingType implements ApiEnum {
  onDemand('on_demand'),
  scheduled('scheduled'),
  intercity('intercity'),
  parcel('parcel'),
  freight('freight'),
  hourly('hourly'),
  unknown('');

  const BookingType(this.value);

  @override
  final String value;

  static BookingType parse(String? raw) =>
      parseApiEnum(BookingType.values, raw, BookingType.unknown);
}
