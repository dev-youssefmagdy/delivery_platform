import 'api_enum.dart';

/// Mirrors backend `PlatformType`; unknown wire values map to [PlatformType.unknown].
enum PlatformType implements ApiEnum {
  android('android'),
  ios('ios'),
  web('web'),
  unknown('');

  const PlatformType(this.value);

  @override
  final String value;

  static PlatformType parse(String? raw) =>
      parseApiEnum(PlatformType.values, raw, PlatformType.unknown);
}
