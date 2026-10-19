import 'api_enum.dart';

/// Mirrors backend `DiscountType`; unknown wire values map to [DiscountType.unknown].
enum DiscountType implements ApiEnum {
  fixed('fixed'),
  percentage('percentage'),
  unknown('');

  const DiscountType(this.value);

  @override
  final String value;

  static DiscountType parse(String? raw) =>
      parseApiEnum(DiscountType.values, raw, DiscountType.unknown);
}
