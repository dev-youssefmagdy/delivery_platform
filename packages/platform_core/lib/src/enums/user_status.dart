import 'api_enum.dart';

/// Mirrors backend `UserStatus`; unknown wire values map to [UserStatus.unknown].
enum UserStatus implements ApiEnum {
  active('active'),
  restricted('restricted'),
  suspended('suspended'),
  verificationRequired('verification_required'),
  deletionPending('deletion_pending'),
  deleted('deleted'),
  unknown('');

  const UserStatus(this.value);

  @override
  final String value;

  static UserStatus parse(String? raw) =>
      parseApiEnum(UserStatus.values, raw, UserStatus.unknown);
}
