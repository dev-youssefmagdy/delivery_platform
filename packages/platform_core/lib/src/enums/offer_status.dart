import 'api_enum.dart';

/// Mirrors backend `OfferStatus`; unknown wire values map to [OfferStatus.unknown].
enum OfferStatus implements ApiEnum {
  submitted('submitted'),
  counterOffered('counter_offered'),
  acceptedByCustomer('accepted_by_customer'),
  rejectedByCustomer('rejected_by_customer'),
  withdrawnByProvider('withdrawn_by_provider'),
  expired('expired'),
  invalidatedRequestChanged('invalidated_request_changed'),
  invalidatedProviderUnavailable('invalidated_provider_unavailable'),
  lostBookingAssigned('lost_booking_assigned'),
  unknown('');

  const OfferStatus(this.value);

  @override
  final String value;

  static OfferStatus parse(String? raw) =>
      parseApiEnum(OfferStatus.values, raw, OfferStatus.unknown);
}
