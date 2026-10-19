import 'api_enum.dart';

/// Mirrors backend `PayoutStatus`; unknown wire values map to [PayoutStatus.unknown].
enum PayoutStatus implements ApiEnum {
  requested('requested'),
  underReview('under_review'),
  approved('approved'),
  processing('processing'),
  paid('paid'),
  failed('failed'),
  rejected('rejected'),
  cancelled('cancelled'),
  reversed('reversed'),
  unknown('');

  const PayoutStatus(this.value);

  @override
  final String value;

  static PayoutStatus parse(String? raw) =>
      parseApiEnum(PayoutStatus.values, raw, PayoutStatus.unknown);
}
