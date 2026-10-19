import 'api_enum.dart';

/// Mirrors backend `ProofType`; unknown wire values map to [ProofType.unknown].
enum ProofType implements ApiEnum {
  photo('photo'),
  signature('signature'),
  recipientOtp('recipient_otp'),
  pickupPin('pickup_pin'),
  checklist('checklist'),
  weight('weight'),
  unknown('');

  const ProofType(this.value);

  @override
  final String value;

  static ProofType parse(String? raw) =>
      parseApiEnum(ProofType.values, raw, ProofType.unknown);
}
