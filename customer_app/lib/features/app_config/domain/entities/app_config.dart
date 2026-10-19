import 'package:platform_core/platform_core.dart';

class AppConfig {
  const AppConfig({
    required this.defaultLanguageCode,
    required this.languageCodes,
    required this.currencyCode,
    required this.currencyExponent,
    required this.digitStyle,
    required this.maintenance,
    this.minVersion,
  });

  final String defaultLanguageCode;
  final List<String> languageCodes;
  final String currencyCode;
  final int currencyExponent;

  /// Null = follow the language.
  final DigitStyle? digitStyle;
  final bool maintenance;
  final String? minVersion;
}
