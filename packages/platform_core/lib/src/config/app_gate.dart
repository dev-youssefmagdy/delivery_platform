/// Whether the app may be used, derived from the server's public config
/// (`GET /config/app`): maintenance mode and the forced-update rule.
enum AppGateStatus {
  /// Normal operation.
  open,

  /// Maintenance mode is on for the mobile apps.
  maintenance,

  /// The installed version is below the minimum and the server forces an update.
  updateRequired,
}

/// Parses "1.2.3" (optionally with a "+build" or "-pre" suffix, or fewer than
/// three parts) into `[major, minor, patch]`; null when it is not a version.
List<int>? parseAppVersion(String? raw) {
  if (raw == null) return null;
  final core = raw.trim().split(RegExp('[+-]')).first;
  final match = RegExp(r'^(\d{1,6})(?:\.(\d{1,6}))?(?:\.(\d{1,6}))?$')
      .firstMatch(core);
  if (match == null) return null;
  return [for (var i = 1; i <= 3; i++) int.parse(match.group(i) ?? '0')];
}

/// Negative when [a] < [b], zero when equal, positive when [a] > [b].
/// Returns null when either side is not a version.
int? compareAppVersions(String? a, String? b) {
  final left = parseAppVersion(a);
  final right = parseAppVersion(b);
  if (left == null || right == null) return null;
  for (var i = 0; i < 3; i++) {
    if (left[i] != right[i]) return left[i].compareTo(right[i]);
  }
  return 0;
}

/// Maintenance wins over a forced update. Unparsable versions never lock a
/// user out (fail open): the server validates the format when it is saved.
AppGateStatus evaluateAppGate({
  required bool maintenance,
  required String? minVersion,
  required bool forceUpdate,
  required String? currentVersion,
}) {
  if (maintenance) return AppGateStatus.maintenance;
  if (!forceUpdate) return AppGateStatus.open;
  final comparison = compareAppVersions(currentVersion, minVersion);
  return comparison != null && comparison < 0
      ? AppGateStatus.updateRequired
      : AppGateStatus.open;
}
