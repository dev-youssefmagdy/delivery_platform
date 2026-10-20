import 'api_enum.dart';

/// Mirrors backend `OnboardingTask`: a pending task on the verification
/// screen. Unknown values map to [OnboardingTask.unknown].
enum OnboardingTask implements ApiEnum {
  completeProfile('complete_profile'),
  selectServices('select_services'),
  addVehicle('add_vehicle'),
  addVehiclePhotos('add_vehicle_photos'),
  submitVehicle('submit_vehicle'),
  uploadDocument('upload_document'),
  replaceRejectedDocument('replace_rejected_document'),
  renewExpiredDocument('renew_expired_document'),
  fixVehicle('fix_vehicle'),
  acceptPolicies('accept_policies'),
  submitApplication('submit_application'),
  unknown('');

  const OnboardingTask(this.value);

  @override
  final String value;

  static OnboardingTask parse(String? raw) =>
      parseApiEnum(OnboardingTask.values, raw, OnboardingTask.unknown);
}
