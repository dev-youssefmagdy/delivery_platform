// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_config_providers.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning

@ProviderFor(appConfigRepository)
final appConfigRepositoryProvider = AppConfigRepositoryProvider._();

final class AppConfigRepositoryProvider
    extends
        $FunctionalProvider<
          AppConfigRepository,
          AppConfigRepository,
          AppConfigRepository
        >
    with $Provider<AppConfigRepository> {
  AppConfigRepositoryProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'appConfigRepositoryProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$appConfigRepositoryHash();

  @$internal
  @override
  $ProviderElement<AppConfigRepository> $createElement(
    $ProviderPointer pointer,
  ) => $ProviderElement(pointer);

  @override
  AppConfigRepository create(Ref ref) {
    return appConfigRepository(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(AppConfigRepository value) {
    return $ProviderOverride(
      origin: this,
      providerOverride: $SyncValueProvider<AppConfigRepository>(value),
    );
  }
}

String _$appConfigRepositoryHash() =>
    r'94f06540e4d724c1cde311d9366eeb9b691ca81d';

/// Startup config: fetched from the server, falling back to the last good
/// copy. Invalidate to refresh (app resume, "check again").

@ProviderFor(appConfig)
final appConfigProvider = AppConfigProvider._();

/// Startup config: fetched from the server, falling back to the last good
/// copy. Invalidate to refresh (app resume, "check again").

final class AppConfigProvider
    extends
        $FunctionalProvider<
          AsyncValue<AppConfig>,
          AppConfig,
          FutureOr<AppConfig>
        >
    with $FutureModifier<AppConfig>, $FutureProvider<AppConfig> {
  /// Startup config: fetched from the server, falling back to the last good
  /// copy. Invalidate to refresh (app resume, "check again").
  AppConfigProvider._()
    : super(
        from: null,
        argument: null,
        retry: _noAutoRetry,
        name: r'appConfigProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$appConfigHash();

  @$internal
  @override
  $FutureProviderElement<AppConfig> $createElement($ProviderPointer pointer) =>
      $FutureProviderElement(pointer);

  @override
  FutureOr<AppConfig> create(Ref ref) {
    return appConfig(ref);
  }
}

String _$appConfigHash() => r'81e2624ce7f918a4ddc513cfe09e13d4405ce9b7';

/// Installed app version (`pubspec` version without the build number).
/// Overridden in tests.

@ProviderFor(appVersion)
final appVersionProvider = AppVersionProvider._();

/// Installed app version (`pubspec` version without the build number).
/// Overridden in tests.

final class AppVersionProvider
    extends $FunctionalProvider<AsyncValue<String>, String, FutureOr<String>>
    with $FutureModifier<String>, $FutureProvider<String> {
  /// Installed app version (`pubspec` version without the build number).
  /// Overridden in tests.
  AppVersionProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'appVersionProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$appVersionHash();

  @$internal
  @override
  $FutureProviderElement<String> $createElement($ProviderPointer pointer) =>
      $FutureProviderElement(pointer);

  @override
  FutureOr<String> create(Ref ref) {
    return appVersion(ref);
  }
}

String _$appVersionHash() => r'c805f17092c94aa3f6b30a3eed1bb098e4cb56c6';

/// Maintenance / forced-update decision for this install. Open while the
/// config or version is still loading or unavailable (never blocks on errors).

@ProviderFor(appGate)
final appGateProvider = AppGateProvider._();

/// Maintenance / forced-update decision for this install. Open while the
/// config or version is still loading or unavailable (never blocks on errors).

final class AppGateProvider
    extends $FunctionalProvider<AppGateStatus, AppGateStatus, AppGateStatus>
    with $Provider<AppGateStatus> {
  /// Maintenance / forced-update decision for this install. Open while the
  /// config or version is still loading or unavailable (never blocks on errors).
  AppGateProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'appGateProvider',
        isAutoDispose: true,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$appGateHash();

  @$internal
  @override
  $ProviderElement<AppGateStatus> $createElement($ProviderPointer pointer) =>
      $ProviderElement(pointer);

  @override
  AppGateStatus create(Ref ref) {
    return appGate(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(AppGateStatus value) {
    return $ProviderOverride(
      origin: this,
      providerOverride: $SyncValueProvider<AppGateStatus>(value),
    );
  }
}

String _$appGateHash() => r'cf8de12c96b93c0dee41297aff2aff6e583d4f66';

/// Money formatting for the current language, with the server's digit style
/// and default-currency symbols. Screens format every amount through this.

@ProviderFor(moneyFormatter)
final moneyFormatterProvider = MoneyFormatterProvider._();

/// Money formatting for the current language, with the server's digit style
/// and default-currency symbols. Screens format every amount through this.

final class MoneyFormatterProvider
    extends $FunctionalProvider<MoneyFormatter, MoneyFormatter, MoneyFormatter>
    with $Provider<MoneyFormatter> {
  /// Money formatting for the current language, with the server's digit style
  /// and default-currency symbols. Screens format every amount through this.
  MoneyFormatterProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'moneyFormatterProvider',
        isAutoDispose: true,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$moneyFormatterHash();

  @$internal
  @override
  $ProviderElement<MoneyFormatter> $createElement($ProviderPointer pointer) =>
      $ProviderElement(pointer);

  @override
  MoneyFormatter create(Ref ref) {
    return moneyFormatter(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(MoneyFormatter value) {
    return $ProviderOverride(
      origin: this,
      providerOverride: $SyncValueProvider<MoneyFormatter>(value),
    );
  }
}

String _$moneyFormatterHash() => r'92754e96dd4fce361bef0c49d7dbf430c107da0a';
