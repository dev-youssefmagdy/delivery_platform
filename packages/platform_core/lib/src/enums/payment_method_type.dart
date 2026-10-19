import 'api_enum.dart';

/// Mirrors backend `PaymentMethodType`; unknown wire values map to [PaymentMethodType.unknown].
enum PaymentMethodType implements ApiEnum {
  cash('cash'),
  card('card'),
  mobileWallet('mobile_wallet'),
  platformWallet('platform_wallet'),
  unknown('');

  const PaymentMethodType(this.value);

  @override
  final String value;

  static PaymentMethodType parse(String? raw) =>
      parseApiEnum(PaymentMethodType.values, raw, PaymentMethodType.unknown);
}
