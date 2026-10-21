/// Parses typed digits (Western digits, `.` decimal) into integer minor
/// units. Null when it is not a valid non-negative amount with at most
/// [exponent] decimals.
int? parseAmountMinor(String input, {int exponent = 2}) {
  final match = RegExp(r'^(\d+)(?:\.(\d*))?$').firstMatch(input.trim());
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
String amountInputText(int minor, {int exponent = 2}) {
  var factor = 1;
  for (var i = 0; i < exponent; i++) {
    factor *= 10;
  }
  final whole = minor ~/ factor;
  final fraction = minor % factor;
  if (exponent == 0 || fraction == 0) return '$whole';
  return '$whole.${fraction.toString().padLeft(exponent, '0').replaceFirst(RegExp(r'0+$'), '')}';
}

enum AmountCheck { ok, empty, belowMin, aboveMax, sameAsFare }

/// Client-side hint only; the server validates the real bounds.
AmountCheck checkAmount(
  int? minor, {
  int? minMinor,
  int? maxMinor,
  int? fareMinor,
}) {
  if (minor == null || minor <= 0) return AmountCheck.empty;
  if (minMinor != null && minor < minMinor) return AmountCheck.belowMin;
  if (maxMinor != null && minor > maxMinor) return AmountCheck.aboveMax;
  if (fareMinor != null && minor == fareMinor) return AmountCheck.sameAsFare;
  return AmountCheck.ok;
}
