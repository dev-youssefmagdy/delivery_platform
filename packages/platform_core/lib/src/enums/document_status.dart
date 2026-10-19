import 'api_enum.dart';

/// Mirrors backend `DocumentStatus`; unknown wire values map to [DocumentStatus.unknown].
enum DocumentStatus implements ApiEnum {
  pending('pending'),
  approved('approved'),
  rejected('rejected'),
  expired('expired'),
  unknown('');

  const DocumentStatus(this.value);

  @override
  final String value;

  static DocumentStatus parse(String? raw) =>
      parseApiEnum(DocumentStatus.values, raw, DocumentStatus.unknown);
}
