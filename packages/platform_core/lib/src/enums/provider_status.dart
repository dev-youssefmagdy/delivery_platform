import 'api_enum.dart';

/// Mirrors backend `ProviderStatus`; unknown wire values map to [ProviderStatus.unknown].
enum ProviderStatus implements ApiEnum {
  draft('draft'),
  phoneVerified('phone_verified'),
  profileSubmitted('profile_submitted'),
  documentsPending('documents_pending'),
  underReview('under_review'),
  approved('approved'),
  moreInformationRequired('more_information_required'),
  rejected('rejected'),
  temporarilySuspended('temporarily_suspended'),
  permanentlySuspended('permanently_suspended'),
  deletionPending('deletion_pending'),
  unknown('');

  const ProviderStatus(this.value);

  @override
  final String value;

  static ProviderStatus parse(String? raw) =>
      parseApiEnum(ProviderStatus.values, raw, ProviderStatus.unknown);
}
