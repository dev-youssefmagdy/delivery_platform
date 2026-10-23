import 'package:flutter/foundation.dart';

/// Targeting of one server flag (`flags.<key>` of `GET /config/app`): the apps
/// and cities it is limited to (empty = everyone) and the rollout percentage.
@immutable
class FlagRule {
  const FlagRule({
    this.apps = const [],
    this.cityIds = const [],
    this.rolloutPercent = 100,
  });

  factory FlagRule.fromJson(Object? json) {
    if (json is! Map) return const FlagRule();
    final percent = json['rollout_percent'];
    return FlagRule(
      apps: [
        for (final a in json['apps'] is List ? json['apps'] as List : const [])
          a.toString(),
      ],
      cityIds: [
        for (final c
            in json['city_ids'] is List ? json['city_ids'] as List : const [])
          if (c is num) c.toInt(),
      ],
      rolloutPercent: percent is num ? percent.toInt().clamp(0, 100) : 100,
    );
  }

  final List<String> apps;
  final List<int> cityIds;
  final int rolloutPercent;
}

/// The one place that decides whether a server feature is on for this client
/// (docs/API.md 8.4): the `features` master switches plus the targeting `flags`.
/// A feature the server did not mention is OFF. The server's own check stays
/// authoritative: this only decides whether the UI is shown at all.
///
/// Evaluation: master switch on; own app in `apps` (if any); own city in
/// `city_ids` (if any; an unknown city fails a city-targeted flag); and when
/// `rollout_percent` < 100 the bucket `crc32("<key>|<subject>") % 100` is below
/// the percentage (no subject fails a partial rollout).
@immutable
class FeatureFlags {
  const FeatureFlags({this.features = const {}, this.rules = const {}});

  factory FeatureFlags.fromJson({Object? features, Object? flags}) =>
      FeatureFlags(
        features: {
          if (features is Map)
            for (final e in features.entries)
              if (e.value is bool) e.key.toString(): e.value as bool,
        },
        rules: {
          if (flags is Map)
            for (final e in flags.entries)
              e.key.toString(): FlagRule.fromJson(e.value),
        },
      );

  static const none = FeatureFlags();

  final Map<String, bool> features;
  final Map<String, FlagRule> rules;

  bool isEnabled(
    String key, {
    required String app,
    int? cityId,
    String? subject,
  }) {
    if (!(features[key] ?? false)) return false;
    final rule = rules[key];
    if (rule == null) return true;
    if (rule.apps.isNotEmpty && !rule.apps.contains(app)) return false;
    if (rule.cityIds.isNotEmpty &&
        (cityId == null || !rule.cityIds.contains(cityId))) {
      return false;
    }
    if (rule.rolloutPercent >= 100) return true;
    if (rule.rolloutPercent <= 0 || subject == null || subject.isEmpty) {
      return false;
    }
    return bucketOf(key, subject) < rule.rolloutPercent;
  }

  /// The stable rollout bucket 0..99 of a subject for a flag (same as the server).
  static int bucketOf(String key, String subject) =>
      crc32('$key|$subject') % 100;
}

final List<int> _crcTable = () {
  final table = List<int>.filled(256, 0);
  for (var n = 0; n < 256; n++) {
    var c = n;
    for (var k = 0; k < 8; k++) {
      c = (c & 1) != 0 ? 0xEDB88320 ^ (c >> 1) : c >> 1;
    }
    table[n] = c;
  }
  return table;
}();

/// CRC-32 (IEEE, like PHP's `crc32()`) of the UTF-8 bytes of [input], as an
/// unsigned 32-bit integer.
int crc32(String input) {
  var crc = 0xFFFFFFFF;
  for (final byte in _utf8(input)) {
    crc = _crcTable[(crc ^ byte) & 0xFF] ^ (crc >> 8);
  }
  return (crc ^ 0xFFFFFFFF) & 0xFFFFFFFF;
}

List<int> _utf8(String s) => _encode(s);

List<int> _encode(String s) {
  final out = <int>[];
  for (final rune in s.runes) {
    if (rune < 0x80) {
      out.add(rune);
    } else if (rune < 0x800) {
      out
        ..add(0xC0 | (rune >> 6))
        ..add(0x80 | (rune & 0x3F));
    } else if (rune < 0x10000) {
      out
        ..add(0xE0 | (rune >> 12))
        ..add(0x80 | ((rune >> 6) & 0x3F))
        ..add(0x80 | (rune & 0x3F));
    } else {
      out
        ..add(0xF0 | (rune >> 18))
        ..add(0x80 | ((rune >> 12) & 0x3F))
        ..add(0x80 | ((rune >> 6) & 0x3F))
        ..add(0x80 | (rune & 0x3F));
    }
  }
  return out;
}

/// The effective update rule of this install: the entry of its own platform
/// from `apps.<app>.platforms` when the server sent one, otherwise the old
/// top-level keys of `apps.<app>` (kept working for older servers).
@immutable
class AppUpdateRule {
  const AppUpdateRule({
    this.minVersion,
    this.latestVersion,
    this.forceUpdate = false,
    this.storeUrl,
  });

  /// [appJson] is `apps.<app>`; [platform] is `android` or `ios`.
  factory AppUpdateRule.fromJson(Object? appJson, {required String platform}) {
    if (appJson is! Map) return const AppUpdateRule();
    final platforms = appJson['platforms'];
    final own = platforms is Map ? platforms[platform] : null;
    final source = own is Map ? own : appJson;
    String? text(Object? v) =>
        v is String && v.trim().isNotEmpty ? v.trim() : null;
    return AppUpdateRule(
      minVersion: text(source['min_version']) ?? text(appJson['min_version']),
      latestVersion: text(source['latest_version']),
      forceUpdate: source['force_update'] is bool
          ? source['force_update'] as bool
          : (appJson['force_update'] is bool &&
                appJson['force_update'] as bool),
      storeUrl: safeStoreUrl(text(source['store_url'])),
    );
  }

  final String? minVersion;
  final String? latestVersion;
  final bool forceUpdate;

  /// An https store page, or null.
  final String? storeUrl;
}

/// Only https links are ever opened by the forced-update screen.
String? safeStoreUrl(String? url) {
  if (url == null) return null;
  final uri = Uri.tryParse(url.trim());
  return uri != null && uri.isScheme('https') && uri.host.isNotEmpty
      ? uri.toString()
      : null;
}

/// `android` / `ios` of the running platform (the keys of `platforms`); other
/// platforms report `android` so the strictest sensible rule applies in tests.
String currentPlatformKey() =>
    defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android';
