/// Immutable money value in integer minor units (mirrors backend `Money`,
/// ADR-04/ADR-30). The server owns all totals; the client only displays them.
class Money implements Comparable<Money> {
  const Money({required this.minor, required this.currency, this.exponent = 2})
    : assert(exponent >= 0 && exponent <= 4);

  /// Parses the API shape `{amount_minor, currency, exponent?}`.
  factory Money.fromJson(Map<String, dynamic> json) => Money(
    minor: (json['amount_minor'] ?? json['minor']) as int,
    currency: json['currency'] as String,
    exponent: (json['exponent'] as int?) ?? 2,
  );

  final int minor;
  final String currency;
  final int exponent;

  Map<String, dynamic> toJson() => {
    'amount_minor': minor,
    'currency': currency,
    'exponent': exponent,
  };

  bool get isZero => minor == 0;
  bool get isNegative => minor < 0;

  int get _factor {
    var f = 1;
    for (var i = 0; i < exponent; i++) {
      f *= 10;
    }
    return f;
  }

  /// Whole units (truncated toward zero).
  int get majorPart => minor ~/ _factor;

  /// Fractional units, always non-negative.
  int get fractionPart => minor.abs() % _factor;

  Money operator +(Money other) => _combine(other, (a, b) => a + b);
  Money operator -(Money other) => _combine(other, (a, b) => a - b);
  Money operator -() =>
      Money(minor: -minor, currency: currency, exponent: exponent);

  Money _combine(Money other, int Function(int, int) op) {
    if (other.currency != currency || other.exponent != exponent) {
      throw ArgumentError('Currency mismatch: $currency vs ${other.currency}');
    }
    return Money(
      minor: op(minor, other.minor),
      currency: currency,
      exponent: exponent,
    );
  }

  @override
  int compareTo(Money other) {
    if (other.currency != currency) {
      throw ArgumentError('Currency mismatch: $currency vs ${other.currency}');
    }
    return minor.compareTo(other.minor);
  }

  @override
  bool operator ==(Object other) =>
      other is Money &&
      other.minor == minor &&
      other.currency == currency &&
      other.exponent == exponent;

  @override
  int get hashCode => Object.hash(minor, currency, exponent);

  @override
  String toString() => 'Money($minor $currency/e$exponent)';
}
