import 'api_enum.dart';

/// Mirrors backend `OfflineReason`; unknown wire values map to [OfflineReason.unknown].
enum OfflineReason implements ApiEnum {
  providerChoice('provider_choice'),
  staleLocation('stale_location'),
  noLongerEligible('no_longer_eligible'),
  accountChanged('account_changed'),
  unknown('');

  const OfflineReason(this.value);

  @override
  final String value;

  static OfflineReason parse(String? raw) =>
      parseApiEnum(OfflineReason.values, raw, OfflineReason.unknown);
}
