import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/providers.dart';
import '../../../map/presentation/providers/map_providers.dart';

/// Last good copy of the safety configuration on the device: the emergency numbers must show without a network.
class _PrefsSafetyCache implements SafetyConfigCache {
  const _PrefsSafetyCache(this._prefs);

  final SharedPreferences _prefs;
  static const _key = 'safety.config.v1';

  @override
  String? read() => _prefs.getString(_key);

  @override
  Future<void> write(String json) => _prefs.setString(_key, json);
}

final safetyRepositoryProvider = Provider<SafetyRepository>(
  (ref) => ApiSafetyRepository(
    SafetyRemoteDataSource(ref.watch(apiClientProvider)),
    cache: _PrefsSafetyCache(ref.watch(sharedPreferencesProvider)),
  ),
);

/// Every provider of the safety feature (SOS, trusted contacts, cases, actions), shared with the screens of `platform_core`.
final safetyProviders = SafetyProviders(safetyRepositoryProvider);

/// The GPS fix for an alert. It never blocks: no permission, no fix or an error simply means the alert goes out without one.
Future<SafetyFix?> Function() safetyLocator(WidgetRef ref) => () async {
  try {
    final service = ref.read(locationServiceProvider);
    if (await service.status() != LocationPermissionState.granted) return null;
    final point = await service.current();
    if (point == null) return null;
    return SafetyFix(latitude: point.latitude, longitude: point.longitude, accuracyMeters: point.accuracyMeters, capturedAt: point.capturedAt ?? DateTime.now());
  } on Object {
    return null;
  }
};
