import 'api_enum.dart';

/// Mirrors backend `VehicleStatus`; unknown wire values map to [VehicleStatus.unknown].
enum VehicleStatus implements ApiEnum {
  draft('draft'),
  underReview('under_review'),
  approved('approved'),
  rejected('rejected'),
  suspended('suspended'),
  inactive('inactive'),
  unknown('');

  const VehicleStatus(this.value);

  @override
  final String value;

  static VehicleStatus parse(String? raw) =>
      parseApiEnum(VehicleStatus.values, raw, VehicleStatus.unknown);
}
