import 'api_enum.dart';

/// Mirrors backend `OfferKind`; unknown wire values map to [OfferKind.unknown].
enum OfferKind implements ApiEnum {
  acceptCustomerFare('accept_customer_fare'),
  counterOffer('counter_offer'),
  unknown('');

  const OfferKind(this.value);

  @override
  final String value;

  static OfferKind parse(String? raw) =>
      parseApiEnum(OfferKind.values, raw, OfferKind.unknown);
}
