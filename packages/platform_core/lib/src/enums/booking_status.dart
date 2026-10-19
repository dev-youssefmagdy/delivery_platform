import 'api_enum.dart';

/// Mirrors backend `BookingStatus`; unknown wire values map to [BookingStatus.unknown].
enum BookingStatus implements ApiEnum {
  draft('draft'),
  searchingOffers('searching_offers'),
  offersReceived('offers_received'),
  expiredNoProvider('expired_no_provider'),
  providerSelected('provider_selected'),
  providerHeadingToPickup('provider_heading_to_pickup'),
  providerArrived('provider_arrived'),
  inProgress('in_progress'),
  deliveryAttemptFailed('delivery_attempt_failed'),
  completedPendingPayment('completed_pending_payment'),
  completed('completed'),
  disputed('disputed'),
  cancelledByCustomer('cancelled_by_customer'),
  cancelledByProvider('cancelled_by_provider'),
  cancelledByOperations('cancelled_by_operations'),
  unknown('');

  const BookingStatus(this.value);

  @override
  final String value;

  static BookingStatus parse(String? raw) =>
      parseApiEnum(BookingStatus.values, raw, BookingStatus.unknown);
}
