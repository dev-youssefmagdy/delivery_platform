import 'package:platform_core/platform_core.dart';

/// Public platform configuration for this app (from `GET /config/app`).
class AppConfig {
  const AppConfig({
    required this.defaultLanguageCode,
    required this.languageCodes,
    required this.countryCode,
    required this.phoneCode,
    required this.currencyCode,
    required this.currencyExponent,
    required this.digitStyle,
    required this.support,
    required this.maintenance,
    this.timezone,
    this.currencySymbols,
    this.minVersion,
    this.forceUpdate = false,
    this.policies = const {},
    this.features = const {},
    this.storeUrl,
    this.flags = FeatureFlags.none,
    this.appKey = 'customer',
    this.fromCache = false,
  });

  final String defaultLanguageCode;
  final List<String> languageCodes;

  /// ISO 3166 alpha-2 code of the default market (preselected in phone input).
  final String countryCode;
  final String phoneCode;
  final String? timezone;
  final String currencyCode;
  final int currencyExponent;

  /// (English symbol, Arabic symbol) of the default currency, when known.
  final (String, String)? currencySymbols;

  /// Null = follow the language.
  final DigitStyle? digitStyle;
  final SupportContacts support;
  final bool maintenance;

  /// Minimum supported version of THIS app and whether older ones are blocked.
  final String? minVersion;
  final bool forceUpdate;

  /// Current published policy version per policy type (`terms`, `privacy` …).
  final Map<String, PolicyRef> policies;

  /// Server feature flags (`features`): for example `wallet`, `offers`. A
  /// missing key means the feature is off.
  final Map<String, bool> features;

  /// The https store page to open on the forced-update screen (this install's
  /// platform), when the server has one.
  final String? storeUrl;

  /// The server flags (master switches + targeting) evaluated by one shared
  /// helper in platform_core. Use [featureEnabled] for a decision.
  final FeatureFlags flags;

  /// Key of this app in the server's `apps` map and in flag targeting.
  final String appKey;

  /// Whether a server feature is on for this app. [cityId] and [subject] (the
  /// signed-in user's id) are needed only by city / percentage targeting; a
  /// feature the server did not mention is off.
  bool featureEnabled(String key, {int? cityId, String? subject}) =>
      flags.isEnabled(key, app: appKey, cityId: cityId, subject: subject);

  /// True when the server could not be reached and the last good copy is used.
  final bool fromCache;

  /// Maintenance / forced-update decision for the installed [currentVersion].
  AppGateStatus gateFor(String? currentVersion) => evaluateAppGate(
    maintenance: maintenance,
    minVersion: minVersion,
    forceUpdate: forceUpdate,
    currentVersion: currentVersion,
  );

  /// Symbols for [MoneyFormatter], or none when the server did not send them.
  Map<String, (String, String)> get moneySymbols =>
      currencySymbols == null ? const {} : {currencyCode: currencySymbols!};
}

class SupportContacts {
  const SupportContacts({this.phone, this.email, this.whatsapp});

  final String? phone;
  final String? email;
  final String? whatsapp;

  /// Non-empty contact values, for display.
  List<String> get lines => [
    for (final v in [phone, email, whatsapp])
      if (v != null && v.trim().isNotEmpty) v.trim(),
  ];
}

class PolicyRef {
  const PolicyRef({required this.id, required this.version, this.publishedAt});

  final String id;
  final int version;
  final DateTime? publishedAt;
}
