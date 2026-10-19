import 'api_enum.dart';

/// Mirrors backend `ActorType`; unknown wire values map to [ActorType.unknown].
enum ActorType implements ApiEnum {
  customer('customer'),
  provider('provider'),
  admin('admin'),
  system('system'),
  unknown('');

  const ActorType(this.value);

  @override
  final String value;

  static ActorType parse(String? raw) =>
      parseApiEnum(ActorType.values, raw, ActorType.unknown);
}
