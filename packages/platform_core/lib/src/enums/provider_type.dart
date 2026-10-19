import 'api_enum.dart';

/// Mirrors backend `ProviderType`; unknown wire values map to [ProviderType.unknown].
enum ProviderType implements ApiEnum {
  individualDriver('individual_driver'),
  courier('courier'),
  movingCrew('moving_crew'),
  commercialTransport('commercial_transport'),
  companyFleet('company_fleet'),
  unknown('');

  const ProviderType(this.value);

  @override
  final String value;

  static ProviderType parse(String? raw) =>
      parseApiEnum(ProviderType.values, raw, ProviderType.unknown);
}
