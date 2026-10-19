import 'package:platform_core/platform_core.dart';

class AppConfigRemoteDataSource {
  const AppConfigRemoteDataSource(this._api);

  final ApiClient _api;

  static const path = '/config/app';

  /// The envelope's `data` object, untouched (so it can be cached verbatim).
  Future<Map<String, dynamic>> fetch() async {
    final result = await _api.get(path);
    final data = result.data;
    if (data is! Map) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return Map<String, dynamic>.from(data);
  }
}
