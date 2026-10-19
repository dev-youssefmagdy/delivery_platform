import 'package:platform_core/platform_core.dart';

import '../../domain/entities/app_config.dart';
import '../../domain/repositories/app_config_repository.dart';
import '../datasources/app_config_local_data_source.dart';
import '../datasources/app_config_remote_data_source.dart';
import '../dtos/app_config_dto.dart';

class AppConfigRepositoryImpl implements AppConfigRepository {
  const AppConfigRepositoryImpl(
    this._remote,
    this._local, {
    this.appKey = customerAppKey,
  });

  /// Key of this app in the server's `apps` map.
  static const customerAppKey = 'customer';

  final AppConfigRemoteDataSource _remote;
  final AppConfigLocalDataSource _local;
  final String appKey;

  @override
  Future<AppConfig> fetch() async {
    try {
      final raw = await _remote.fetch();
      final config = _parse(raw, fromCache: false);
      await _local.write(raw);
      return config;
    } on ApiException catch (e) {
      if (e.kind == ApiFailureKind.cancelled) rethrow;
      final cached = _cached();
      if (cached != null) return cached;
      rethrow;
    }
  }

  AppConfig? _cached() {
    final raw = _local.read();
    if (raw == null) return null;
    try {
      return _parse(raw, fromCache: true);
    } on ApiException {
      return null;
    }
  }

  AppConfig _parse(Map<String, dynamic> raw, {required bool fromCache}) {
    final AppConfigDto dto;
    try {
      dto = AppConfigDto.fromJson(raw);
    } on Object {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return _toEntity(dto, fromCache: fromCache);
  }

  AppConfig _toEntity(AppConfigDto dto, {required bool fromCache}) {
    final languages = dto.locales
        .where(supportedLanguageCodes.contains)
        .toList(growable: false);
    final defaultLanguage = supportedLanguageCodes.contains(dto.defaultLocale)
        ? dto.defaultLocale!
        : defaultLanguageCode;
    final symbols = dto.currency?.symbol;
    final rule = dto.apps[appKey];
    return AppConfig(
      defaultLanguageCode: defaultLanguage,
      languageCodes: languages.isEmpty ? supportedLanguageCodes : languages,
      countryCode: dto.country?.iso2 ?? 'EG',
      phoneCode: dto.country?.phoneCode ?? '+20',
      timezone: dto.country?.timezone,
      currencyCode: dto.currency?.code ?? 'EGP',
      currencyExponent: dto.currency?.exponent ?? 2,
      currencySymbols: symbols == null || symbols.isEmpty
          ? null
          : (
              symbols['en'] ?? dto.currency!.code,
              symbols['ar'] ?? dto.currency!.code,
            ),
      digitStyle: switch (dto.digitsPreference) {
        'arabic_indic' => DigitStyle.arabicIndic,
        'western' => DigitStyle.western,
        _ => null,
      },
      support: SupportContacts(
        phone: dto.support?.phone,
        email: dto.support?.email,
        whatsapp: dto.support?.whatsapp,
      ),
      maintenance: dto.maintenance,
      minVersion: rule?.minVersion,
      forceUpdate: rule?.forceUpdate ?? false,
      policies: {
        for (final e in dto.policies.entries)
          e.key: PolicyRef(
            id: e.value.id,
            version: e.value.version,
            publishedAt: DateTime.tryParse(e.value.publishedAt ?? ''),
          ),
      },
      fromCache: fromCache,
    );
  }
}
