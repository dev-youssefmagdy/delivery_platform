import 'api_enum.dart';

/// Mirrors backend `ContentStatus`; unknown wire values map to [ContentStatus.unknown].
enum ContentStatus implements ApiEnum {
  draft('draft'),
  inReview('in_review'),
  scheduled('scheduled'),
  published('published'),
  archived('archived'),
  unknown('');

  const ContentStatus(this.value);

  @override
  final String value;

  static ContentStatus parse(String? raw) =>
      parseApiEnum(ContentStatus.values, raw, ContentStatus.unknown);
}
