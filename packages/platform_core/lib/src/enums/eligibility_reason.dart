import 'api_enum.dart';

/// Mirrors backend `EligibilityReason` (why a provider cannot work);
/// unknown wire values map to [EligibilityReason.unknown].
enum EligibilityReason implements ApiEnum {
  notApproved('not_approved'),
  providerSuspended('provider_suspended'),
  accountBlocked('account_blocked'),
  documentMissing('document_missing'),
  documentNotApproved('document_not_approved'),
  documentExpired('document_expired'),
  noApprovedVehicle('no_approved_vehicle'),
  vehicleNotApproved('vehicle_not_approved'),
  serviceNotApproved('service_not_approved'),
  vehicleCategoryMismatch('vehicle_category_mismatch'),
  unknown('');

  const EligibilityReason(this.value);

  @override
  final String value;

  static EligibilityReason parse(String? raw) =>
      parseApiEnum(EligibilityReason.values, raw, EligibilityReason.unknown);
}
