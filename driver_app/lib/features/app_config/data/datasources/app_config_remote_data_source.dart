import 'package:platform_core/platform_core.dart';

import '../dtos/app_config_dto.dart';

class AppConfigRemoteDataSource {
  const AppConfigRemoteDataSource(this._api);

  final ApiClient _api;

  static const path = '/config/app';

  Future<AppConfigDto> fetch() async {
    final result = await _api.get(path);
    final data = result.data;
    if (data is! Map) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return AppConfigDto.fromJson(Map<String, dynamic>.from(data));
  }
}
