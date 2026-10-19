import 'api_enum.dart';

/// Mirrors backend `CouponStatus`; unknown wire values map to [CouponStatus.unknown].
enum CouponStatus implements ApiEnum {
  draft('draft'),
  scheduled('scheduled'),
  active('active'),
  paused('paused'),
  exhausted('exhausted'),
  expired('expired'),
  archived('archived'),
  unknown('');

  const CouponStatus(this.value);

  @override
  final String value;

  static CouponStatus parse(String? raw) =>
      parseApiEnum(CouponStatus.values, raw, CouponStatus.unknown);
}
