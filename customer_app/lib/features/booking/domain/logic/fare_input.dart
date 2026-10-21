/// Parses what the customer typed (Western or Arabic-Indic digits, `.` or
/// `٫` decimal separator) into integer minor units. Returns null when it is
/// not a valid non-negative amount with at most [exponent] decimals.
int? parseFareMinor(String input, {int exponent = 2}) {
  const indic = '٠١٢٣٤٥٦٧٨٩';
  const persian = '۰۱۲۳۴۵۶۷۸۹';
  final buffer = StringBuffer();
  for (final unit in input.trim().runes) {
    final ch = String.fromCharCode(unit);
    final i = indic.indexOf(ch);
    final p = persian.indexOf(ch);
    if (i >= 0) {
      buffer.write(i);
    } else if (p >= 0) {
      buffer.write(p);
    } else if (ch == '٫' || ch == ',') {
      buffer.write('.');
    } else {
      buffer.write(ch);
    }
  }
  final text = buffer.toString();
  final match = RegExp(r'^(\d+)(?:\.(\d+))?$').firstMatch(text);
  if (match == null) return null;
  final whole = int.tryParse(match.group(1)!);
  final fraction = match.group(2) ?? '';
  if (whole == null || fraction.length > exponent) return null;
  var factor = 1;
  for (var i = 0; i < exponent; i++) {
    factor *= 10;
  }
  final fractionMinor = fraction.isEmpty
      ? 0
      : int.parse(fraction.padRight(exponent, '0'));
  return whole * factor + fractionMinor;
}

/// Editable text for [minor], with no currency symbol or grouping.
String fareInputText(int minor, {int exponent = 2}) {
  var factor = 1;
  for (var i = 0; i < exponent; i++) {
    factor *= 10;
  }
  final whole = minor ~/ factor;
  final fraction = minor % factor;
  if (exponent == 0 || fraction == 0) return '$whole';
  return '$whole.${fraction.toString().padLeft(exponent, '0').replaceFirst(RegExp(r'0+$'), '')}';
}

enum FareCheck { ok, empty, invalid, belowMinimum, aboveMaximum }

/// Client-side hint only; the server validates the real bounds.
FareCheck checkFare(int? minor, {int? minMinor, int? maxMinor}) {
  if (minor == null) return FareCheck.invalid;
  if (minor <= 0) return FareCheck.empty;
  if (minMinor != null && minor < minMinor) return FareCheck.belowMinimum;
  if (maxMinor != null && minor > maxMinor) return FareCheck.aboveMaximum;
  return FareCheck.ok;
}
