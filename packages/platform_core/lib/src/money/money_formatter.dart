import 'money.dart';

/// Digit rendering preference (SRS §3: Arabic-Indic and Western digits as a
/// display preference).
enum DigitStyle { arabicIndic, western }

/// Formats [Money] for display. Mirrors the backend formatter: Arabic puts the
/// symbol after the amount, English before it.
class MoneyFormatter {
  const MoneyFormatter({
    required this.languageCode,
    this.digits,
    this.symbols = const {'EGP': ('EGP', 'ج.م')},
  });

  final String languageCode;

  /// Null means "follow the language" (Arabic-Indic for `ar`, Western else).
  final DigitStyle? digits;

  /// currency code to (english symbol, arabic symbol).
  final Map<String, (String, String)> symbols;

  bool get _isArabic => languageCode == 'ar';

  DigitStyle get _digitStyle =>
      digits ?? (_isArabic ? DigitStyle.arabicIndic : DigitStyle.western);

  String format(Money money) {
    final indic = _digitStyle == DigitStyle.arabicIndic;
    final sep = indic ? '٬' : ',';
    final dec = indic ? '٫' : '.';
    final major = _group(money.majorPart.abs(), sep);
    var number = major;
    if (money.exponent > 0) {
      number +=
          dec + money.fractionPart.toString().padLeft(money.exponent, '0');
    }
    number = _applyDigits(number);
    final negative = money.isNegative;
    final sym = symbols[money.currency];
    final symbol = sym == null ? money.currency : (_isArabic ? sym.$2 : sym.$1);
    final sign = negative ? '-' : '';
    return _isArabic ? '$sign$number $symbol' : '$sign$symbol $number';
  }

  String _group(int value, String sep) {
    final s = value.toString();
    final out = StringBuffer();
    for (var i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) out.write(sep);
      out.write(s[i]);
    }
    return out.toString();
  }

  String _applyDigits(String input) {
    if (_digitStyle == DigitStyle.western) return input;
    const indic = '٠١٢٣٤٥٦٧٨٩';
    return input.replaceAllMapped(
      RegExp('[0-9]'),
      (m) => indic[int.parse(m[0]!)],
    );
  }
}
