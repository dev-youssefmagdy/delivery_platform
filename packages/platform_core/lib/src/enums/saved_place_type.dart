import 'api_enum.dart';

/// Mirrors backend `SavedPlaceType`.
enum SavedPlaceType implements ApiEnum {
  home('home'),
  work('work'),
  other('other'),
  unknown('');

  const SavedPlaceType(this.value);

  @override
  final String value;

  static SavedPlaceType parse(String? raw) =>
      parseApiEnum(SavedPlaceType.values, raw, SavedPlaceType.other);
}
