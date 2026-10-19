/// Contract for enums mirrored from the backend. Every mirrored enum has an
/// `unknown` member so new server values never crash older clients
/// (Plan §6.2, docs/API.md §1).
abstract interface class ApiEnum {
  /// The exact string used on the wire.
  String get value;
}

/// Finds the member of [values] whose wire value equals [raw], or [fallback].
T parseApiEnum<T extends ApiEnum>(List<T> values, String? raw, T fallback) {
  if (raw == null) return fallback;
  for (final item in values) {
    if (item.value == raw) return item;
  }
  return fallback;
}
