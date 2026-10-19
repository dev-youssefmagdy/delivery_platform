import 'api_enum.dart';

/// Mirrors backend `ServiceFamilyKind`; unknown wire values map to [ServiceFamilyKind.unknown].
enum ServiceFamilyKind implements ApiEnum {
  passengerCity('passenger_city'),
  intercity('intercity'),
  courier('courier'),
  moving('moving'),
  freight('freight'),
  hourly('hourly'),
  other('other'),
  unknown('');

  const ServiceFamilyKind(this.value);

  @override
  final String value;

  static ServiceFamilyKind parse(String? raw) =>
      parseApiEnum(ServiceFamilyKind.values, raw, ServiceFamilyKind.unknown);
}
