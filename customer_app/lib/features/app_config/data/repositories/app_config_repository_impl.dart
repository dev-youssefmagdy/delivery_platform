import 'package:platform_core/platform_core.dart';

import '../../domain/entities/app_config.dart';
import '../../domain/repositories/app_config_repository.dart';
import '../datasources/app_config_remote_data_source.dart';
import '../dtos/app_config_dto.dart';

class AppConfigRepositoryImpl implements AppConfigRepository {
  const AppConfigRepositoryImpl(this._remote);

  final AppConfigRemoteDataSource _remote;

  @override
  Future<AppConfig> fetch() async => _toEntity(await _remote.fetch());

  AppConfig _toEntity(AppConfigDto dto) {
    final languages = dto.locales
        .where(supportedLanguageCodes.contains)
        .toList(growable: false);
    return AppConfig(
      defaultLanguageCode: supportedLanguageCodes.contains(dto.defaultLocale)
          ? dto.defaultLocale!
          : defaultLanguageCode,
      languageCodes: languages.isEmpty ? supportedLanguageCodes : languages,
      currencyCode: dto.currency?.code ?? 'EGP',
      currencyExponent: dto.currency?.exponent ?? 2,
      digitStyle: switch (dto.digitsPreference) {
        'arabic_indic' => DigitStyle.arabicIndic,
        'western' => DigitStyle.western,
        _ => null,
      },
      maintenance: dto.maintenance,
      minVersion: dto.minVersion,
    );
  }
}
