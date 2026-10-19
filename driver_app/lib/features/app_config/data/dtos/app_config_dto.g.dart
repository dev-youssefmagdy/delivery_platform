// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_config_dto.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_AppConfigDto _$AppConfigDtoFromJson(
  Map<String, dynamic> json,
) => _AppConfigDto(
  defaultLocale: json['default_locale'] as String?,
  locales:
      (json['locales'] as List<dynamic>?)?.map((e) => e as String).toList() ??
      const <String>[],
  country: json['country'] == null
      ? null
      : CountryDto.fromJson(json['country'] as Map<String, dynamic>),
  currency: json['currency'] == null
      ? null
      : CurrencyDto.fromJson(json['currency'] as Map<String, dynamic>),
  digitsPreference: json['digits_preference'] as String?,
  support: json['support'] == null
      ? null
      : SupportDto.fromJson(json['support'] as Map<String, dynamic>),
  maintenance: json['maintenance'] as bool? ?? false,
  apps:
      (json['apps'] as Map<String, dynamic>?)?.map(
        (k, e) =>
            MapEntry(k, AppVersionRuleDto.fromJson(e as Map<String, dynamic>)),
      ) ??
      const <String, AppVersionRuleDto>{},
  policies:
      (json['policies'] as Map<String, dynamic>?)?.map(
        (k, e) =>
            MapEntry(k, PolicyVersionDto.fromJson(e as Map<String, dynamic>)),
      ) ??
      const <String, PolicyVersionDto>{},
  features:
      (json['features'] as Map<String, dynamic>?)?.map(
        (k, e) => MapEntry(k, e as bool),
      ) ??
      const <String, bool>{},
);

Map<String, dynamic> _$AppConfigDtoToJson(_AppConfigDto instance) =>
    <String, dynamic>{
      'default_locale': instance.defaultLocale,
      'locales': instance.locales,
      'country': instance.country,
      'currency': instance.currency,
      'digits_preference': instance.digitsPreference,
      'support': instance.support,
      'maintenance': instance.maintenance,
      'apps': instance.apps,
      'policies': instance.policies,
      'features': instance.features,
    };

_CountryDto _$CountryDtoFromJson(Map<String, dynamic> json) => _CountryDto(
  iso2: json['iso2'] as String,
  phoneCode: json['phone_code'] as String?,
  timezone: json['timezone'] as String?,
);

Map<String, dynamic> _$CountryDtoToJson(_CountryDto instance) =>
    <String, dynamic>{
      'iso2': instance.iso2,
      'phone_code': instance.phoneCode,
      'timezone': instance.timezone,
    };

_CurrencyDto _$CurrencyDtoFromJson(Map<String, dynamic> json) => _CurrencyDto(
  code: json['code'] as String,
  exponent: (json['exponent'] as num?)?.toInt() ?? 2,
  symbol:
      (json['symbol'] as Map<String, dynamic>?)?.map(
        (k, e) => MapEntry(k, e as String),
      ) ??
      const <String, String>{},
);

Map<String, dynamic> _$CurrencyDtoToJson(_CurrencyDto instance) =>
    <String, dynamic>{
      'code': instance.code,
      'exponent': instance.exponent,
      'symbol': instance.symbol,
    };

_SupportDto _$SupportDtoFromJson(Map<String, dynamic> json) => _SupportDto(
  phone: json['phone'] as String?,
  email: json['email'] as String?,
  whatsapp: json['whatsapp'] as String?,
);

Map<String, dynamic> _$SupportDtoToJson(_SupportDto instance) =>
    <String, dynamic>{
      'phone': instance.phone,
      'email': instance.email,
      'whatsapp': instance.whatsapp,
    };

_AppVersionRuleDto _$AppVersionRuleDtoFromJson(Map<String, dynamic> json) =>
    _AppVersionRuleDto(
      minVersion: json['min_version'] as String?,
      forceUpdate: json['force_update'] as bool? ?? false,
    );

Map<String, dynamic> _$AppVersionRuleDtoToJson(_AppVersionRuleDto instance) =>
    <String, dynamic>{
      'min_version': instance.minVersion,
      'force_update': instance.forceUpdate,
    };

_PolicyVersionDto _$PolicyVersionDtoFromJson(Map<String, dynamic> json) =>
    _PolicyVersionDto(
      id: json['id'] as String,
      version: (json['version'] as num).toInt(),
      publishedAt: json['published_at'] as String?,
    );

Map<String, dynamic> _$PolicyVersionDtoToJson(_PolicyVersionDto instance) =>
    <String, dynamic>{
      'id': instance.id,
      'version': instance.version,
      'published_at': instance.publishedAt,
    };
