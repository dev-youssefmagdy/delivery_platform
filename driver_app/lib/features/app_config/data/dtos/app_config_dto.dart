import 'package:freezed_annotation/freezed_annotation.dart';

part 'app_config_dto.freezed.dart';
part 'app_config_dto.g.dart';

/// Wire shape of `GET /api/v1/config/app` (docs/API.md §8.4). Every field is
/// optional or defaulted so older/newer servers never crash the client;
/// unknown keys are ignored.
@freezed
abstract class AppConfigDto with _$AppConfigDto {
  const factory AppConfigDto({
    @JsonKey(name: 'default_locale') String? defaultLocale,
    @Default(<String>[]) List<String> locales,
    CountryDto? country,
    CurrencyDto? currency,
    @JsonKey(name: 'digits_preference') String? digitsPreference,
    SupportDto? support,
    @Default(false) bool maintenance,

    /// Per-app version rules keyed by app: `customer`, `provider`.
    @Default(<String, AppVersionRuleDto>{}) Map<String, AppVersionRuleDto> apps,

    /// Current published policy version per policy type.
    @Default(<String, PolicyVersionDto>{})
    Map<String, PolicyVersionDto> policies,
    @Default(<String, bool>{}) Map<String, bool> features,

    /// Targeting of the server flags (apps, cities, rollout percentage).
    @Default(<String, dynamic>{}) Map<String, dynamic> flags,
  }) = _AppConfigDto;

  factory AppConfigDto.fromJson(Map<String, dynamic> json) =>
      _$AppConfigDtoFromJson(json);
}

@freezed
abstract class CountryDto with _$CountryDto {
  const factory CountryDto({
    required String iso2,
    @JsonKey(name: 'phone_code') String? phoneCode,
    String? timezone,
  }) = _CountryDto;

  factory CountryDto.fromJson(Map<String, dynamic> json) =>
      _$CountryDtoFromJson(json);
}

@freezed
abstract class CurrencyDto with _$CurrencyDto {
  const factory CurrencyDto({
    required String code,
    @Default(2) int exponent,

    /// Symbols by language code (`ar`, `en`).
    @Default(<String, String>{}) Map<String, String> symbol,
  }) = _CurrencyDto;

  factory CurrencyDto.fromJson(Map<String, dynamic> json) =>
      _$CurrencyDtoFromJson(json);
}

@freezed
abstract class SupportDto with _$SupportDto {
  const factory SupportDto({String? phone, String? email, String? whatsapp}) =
      _SupportDto;

  factory SupportDto.fromJson(Map<String, dynamic> json) =>
      _$SupportDtoFromJson(json);
}

@freezed
abstract class AppVersionRuleDto with _$AppVersionRuleDto {
  const factory AppVersionRuleDto({
    @JsonKey(name: 'min_version') String? minVersion,
    @JsonKey(name: 'force_update') @Default(false) bool forceUpdate,

    /// Per-platform rules (`android`, `ios`) with the store page to open.
    @Default(<String, PlatformRuleDto>{}) Map<String, PlatformRuleDto> platforms,
  }) = _AppVersionRuleDto;

  factory AppVersionRuleDto.fromJson(Map<String, dynamic> json) =>
      _$AppVersionRuleDtoFromJson(json);
}

@freezed
abstract class PlatformRuleDto with _$PlatformRuleDto {
  const factory PlatformRuleDto({
    @JsonKey(name: 'min_version') String? minVersion,
    @JsonKey(name: 'latest_version') String? latestVersion,
    @JsonKey(name: 'force_update') @Default(false) bool forceUpdate,
    @JsonKey(name: 'store_url') String? storeUrl,
  }) = _PlatformRuleDto;

  factory PlatformRuleDto.fromJson(Map<String, dynamic> json) =>
      _$PlatformRuleDtoFromJson(json);
}

@freezed
abstract class PolicyVersionDto with _$PolicyVersionDto {
  const factory PolicyVersionDto({
    required String id,
    required int version,
    @JsonKey(name: 'published_at') String? publishedAt,
  }) = _PolicyVersionDto;

  factory PolicyVersionDto.fromJson(Map<String, dynamic> json) =>
      _$PolicyVersionDtoFromJson(json);
}
