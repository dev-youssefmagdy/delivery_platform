import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// Last good `GET /config/app` payload, so the app still knows its market,
/// languages and maintenance/update rules when the server is unreachable.
class AppConfigLocalDataSource {
  const AppConfigLocalDataSource(this._prefs);

  final SharedPreferences _prefs;

  static const key = 'app_config_cache_v1';

  Map<String, dynamic>? read() {
    final raw = _prefs.getString(key);
    if (raw == null) return null;
    try {
      final decoded = jsonDecode(raw);
      return decoded is Map ? Map<String, dynamic>.from(decoded) : null;
    } on FormatException {
      return null;
    }
  }

  Future<void> write(Map<String, dynamic> payload) =>
      _prefs.setString(key, jsonEncode(payload));
}
