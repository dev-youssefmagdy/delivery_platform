import 'api_enum.dart';

/// Mirrors backend `AppLocale`; unknown wire values map to [AppLocale.unknown].
enum AppLocale implements ApiEnum {
  ar('ar'),
  en('en'),
  unknown('');

  const AppLocale(this.value);

  @override
  final String value;

  static AppLocale parse(String? raw) =>
      parseApiEnum(AppLocale.values, raw, AppLocale.unknown);
}
