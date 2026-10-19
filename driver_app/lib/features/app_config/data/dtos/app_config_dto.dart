import 'package:freezed_annotation/freezed_annotation.dart';

part 'app_config_dto.freezed.dart';
part 'app_config_dto.g.dart';

/// Wire shape of `GET /api/v1/config/app` (server contract lands in P1.2).
/// Every field is optional so older/newer servers never crash the client.
@freezed
abstract class AppConfigDto with _$AppConfigDto {
  const factory AppConfigDto({
    @JsonKey(name: 'default_locale') String? defaultLocale,
    @Default(<String>[]) List<String> locales,
    CurrencyDto? currency,
    @JsonKey(name: 'digits_preference') String? digitsPreference,
    @Default(false) bool maintenance,
    @JsonKey(name: 'min_version') String? minVersion,
  }) = _AppConfigDto;

  factory AppConfigDto.fromJson(Map<String, dynamic> json) =>
      _$AppConfigDtoFromJson(json);
}

@freezed
abstract class CurrencyDto with _$CurrencyDto {
  const factory CurrencyDto({required String code, @Default(2) int exponent}) =
      _CurrencyDto;

  factory CurrencyDto.fromJson(Map<String, dynamic> json) =>
      _$CurrencyDtoFromJson(json);
}
