import 'package:platform_core/platform_core.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../../core/providers.dart';
import '../../data/datasources/app_config_local_data_source.dart';
import '../../data/datasources/app_config_remote_data_source.dart';
import '../../data/repositories/app_config_repository_impl.dart';
import '../../domain/entities/app_config.dart';
import '../../domain/repositories/app_config_repository.dart';

part 'app_config_providers.g.dart';

@Riverpod(keepAlive: true)
AppConfigRepository appConfigRepository(Ref ref) => AppConfigRepositoryImpl(
  AppConfigRemoteDataSource(ref.watch(apiClientProvider)),
  AppConfigLocalDataSource(ref.watch(sharedPreferencesProvider)),
);

/// The config is re-read explicitly (app resume, "check again") and falls back
/// to the cache itself, so Riverpod's automatic retry would only delay the
/// splash screen.
Duration? _noAutoRetry(int retryCount, Object error) => null;

/// Startup config: fetched from the server, falling back to the last good
/// copy. Invalidate to refresh (app resume, "check again").
@Riverpod(keepAlive: true, retry: _noAutoRetry)
Future<AppConfig> appConfig(Ref ref) =>
    ref.watch(appConfigRepositoryProvider).fetch();

/// Installed app version (`pubspec` version without the build number).
/// Overridden in tests.
@Riverpod(keepAlive: true)
Future<String> appVersion(Ref ref) => installedAppVersion();

/// Maintenance / forced-update decision for this install. Open while the
/// config or version is still loading or unavailable (never blocks on errors).
@riverpod
AppGateStatus appGate(Ref ref) {
  final config = ref.watch(appConfigProvider).value;
  final version = ref.watch(appVersionProvider).value;
  return config?.gateFor(version) ?? AppGateStatus.open;
}

/// Money formatting for the current language, with the server's digit style
/// and default-currency symbols. Screens format every amount through this.
@riverpod
MoneyFormatter moneyFormatter(Ref ref) {
  final config = ref.watch(appConfigProvider).value;
  final language = ref.watch(localeProvider).languageCode;
  final symbols = config?.moneySymbols ?? const <String, (String, String)>{};
  return symbols.isEmpty
      ? MoneyFormatter(languageCode: language, digits: config?.digitStyle)
      : MoneyFormatter(
          languageCode: language,
          digits: config?.digitStyle,
          symbols: symbols,
        );
}
