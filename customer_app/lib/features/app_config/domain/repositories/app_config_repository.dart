import '../entities/app_config.dart';

abstract interface class AppConfigRepository {
  /// Throws `ApiException` on failure.
  Future<AppConfig> fetch();
}
