import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../../core/providers.dart';
import '../../data/datasources/app_config_remote_data_source.dart';
import '../../data/repositories/app_config_repository_impl.dart';
import '../../domain/entities/app_config.dart';
import '../../domain/repositories/app_config_repository.dart';

part 'app_config_providers.g.dart';

@Riverpod(keepAlive: true)
AppConfigRepository appConfigRepository(Ref ref) => AppConfigRepositoryImpl(
  AppConfigRemoteDataSource(ref.watch(apiClientProvider)),
);

/// Startup config. Not consumed by a screen until P1.2 ships the endpoint.
@riverpod
Future<AppConfig> appConfig(Ref ref) =>
    ref.watch(appConfigRepositoryProvider).fetch();
