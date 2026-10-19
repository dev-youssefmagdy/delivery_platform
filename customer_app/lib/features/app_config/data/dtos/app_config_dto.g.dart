// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_config_dto.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_AppConfigDto _$AppConfigDtoFromJson(Map<String, dynamic> json) =>
    _AppConfigDto(
      defaultLocale: json['default_locale'] as String?,
      locales:
          (json['locales'] as List<dynamic>?)
              ?.map((e) => e as String)
              .toList() ??
          const <String>[],
      currency: json['currency'] == null
          ? null
          : CurrencyDto.fromJson(json['currency'] as Map<String, dynamic>),
      digitsPreference: json['digits_preference'] as String?,
      maintenance: json['maintenance'] as bool? ?? false,
      minVersion: json['min_version'] as String?,
    );

Map<String, dynamic> _$AppConfigDtoToJson(_AppConfigDto instance) =>
    <String, dynamic>{
      'default_locale': instance.defaultLocale,
      'locales': instance.locales,
      'currency': instance.currency,
      'digits_preference': instance.digitsPreference,
      'maintenance': instance.maintenance,
      'min_version': instance.minVersion,
    };

_CurrencyDto _$CurrencyDtoFromJson(Map<String, dynamic> json) => _CurrencyDto(
  code: json['code'] as String,
  exponent: (json['exponent'] as num?)?.toInt() ?? 2,
);

Map<String, dynamic> _$CurrencyDtoToJson(_CurrencyDto instance) =>
    <String, dynamic>{'code': instance.code, 'exponent': instance.exponent};
