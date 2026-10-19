import 'api_enum.dart';

/// Mirrors backend `ModerationStatus`; unknown wire values map to [ModerationStatus.unknown].
enum ModerationStatus implements ApiEnum {
  visible('visible'),
  hidden('hidden'),
  underReview('under_review'),
  unknown('');

  const ModerationStatus(this.value);

  @override
  final String value;

  static ModerationStatus parse(String? raw) =>
      parseApiEnum(ModerationStatus.values, raw, ModerationStatus.unknown);
}
