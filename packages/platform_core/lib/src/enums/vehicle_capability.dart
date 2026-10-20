import 'api_enum.dart';

/// Mirrors backend `VehicleCapability`; unknown values are dropped by
/// [VehicleCapability.parse] callers or shown through [VehicleCapability.unknown].
enum VehicleCapability implements ApiEnum {
  airConditioning('air_conditioning'),
  childSeat('child_seat'),
  wheelchairAccessible('wheelchair_accessible'),
  petsAllowed('pets_allowed'),
  largeLuggage('large_luggage'),
  coveredCargo('covered_cargo'),
  tailLift('tail_lift'),
  refrigerated('refrigerated'),
  movingEquipment('moving_equipment'),
  unknown('');

  const VehicleCapability(this.value);

  @override
  final String value;

  static VehicleCapability parse(String? raw) =>
      parseApiEnum(VehicleCapability.values, raw, VehicleCapability.unknown);
}
