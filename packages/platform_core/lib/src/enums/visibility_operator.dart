import 'api_enum.dart';

/// Mirrors backend `VisibilityOperator` (comparison of a form field's
/// `visible_if`). Unknown wire values map to [VisibilityOperator.unknown],
/// which never matches, so a field with a condition this client does not
/// understand stays hidden instead of being shown wrongly.
enum VisibilityOperator implements ApiEnum {
  equals('eq'),
  notEquals('neq'),
  isIn('in'),
  notIn('not_in'),
  greaterThan('gt'),
  greaterOrEqual('gte'),
  lessThan('lt'),
  lessOrEqual('lte'),
  filled('filled'),
  empty('empty'),
  unknown('');

  const VisibilityOperator(this.value);

  @override
  final String value;

  static VisibilityOperator parse(String? raw) =>
      parseApiEnum(VisibilityOperator.values, raw, VisibilityOperator.unknown);

  /// Same semantics as PHP `VisibilityOperator::matches()`.
  bool matches(Object? actual, Object? expected) {
    final isFilled = _isFilled(actual);
    switch (this) {
      case VisibilityOperator.filled:
        return isFilled;
      case VisibilityOperator.empty:
        return !isFilled;
      case VisibilityOperator.equals:
        return isFilled && _loose(actual) == _loose(expected);
      case VisibilityOperator.notEquals:
        return !isFilled || _loose(actual) != _loose(expected);
      case VisibilityOperator.isIn:
        return isFilled && _contains(expected, actual);
      case VisibilityOperator.notIn:
        return !isFilled || !_contains(expected, actual);
      case VisibilityOperator.greaterThan:
        return _compare(actual, expected, (a, b) => a > b);
      case VisibilityOperator.greaterOrEqual:
        return _compare(actual, expected, (a, b) => a >= b);
      case VisibilityOperator.lessThan:
        return _compare(actual, expected, (a, b) => a < b);
      case VisibilityOperator.lessOrEqual:
        return _compare(actual, expected, (a, b) => a <= b);
      case VisibilityOperator.unknown:
        return false;
    }
  }

  static bool _isFilled(Object? v) =>
      !(v == null || v == '' || (v is Iterable && v.isEmpty));

  /// Booleans, numbers and strings compare by their string form so `1`, `"1"`
  /// and `true` agree with the JSON schema.
  static String _loose(Object? v) {
    if (v is bool) return v ? '1' : '0';
    if (v is num) {
      return v == v.truncate() ? v.truncate().toString() : v.toString();
    }
    return '$v';
  }

  static bool _contains(Object? haystack, Object? actual) {
    final candidates = haystack is Iterable ? haystack : [haystack];
    final values = actual is Iterable ? actual : [actual];
    for (final v in values) {
      for (final c in candidates) {
        if (_loose(v) == _loose(c)) return true;
      }
    }
    return false;
  }

  static bool _compare(
    Object? actual,
    Object? expected,
    bool Function(num, num) test,
  ) {
    if (!_isFilled(actual)) return false;
    final a = actual is num ? actual : num.tryParse('$actual');
    final b = expected is num ? expected : num.tryParse('$expected');
    return a != null && b != null && test(a, b);
  }
}
