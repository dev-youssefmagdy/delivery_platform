import 'api_enum.dart';

/// Mirrors backend `VehiclePhotoKind`.
enum VehiclePhotoKind implements ApiEnum {
  front('front'),
  back('back'),
  side('side'),
  interior('interior'),
  other('other'),
  unknown('');

  const VehiclePhotoKind(this.value);

  @override
  final String value;

  static VehiclePhotoKind parse(String? raw) =>
      parseApiEnum(VehiclePhotoKind.values, raw, VehiclePhotoKind.unknown);
}
