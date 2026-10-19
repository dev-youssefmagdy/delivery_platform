import 'package:connectivity_plus/connectivity_plus.dart';

/// Emits true while the device has any network transport. (Transport presence,
/// not proof of internet; API errors still surface as offline states.)
Stream<bool> onlineStream() async* {
  final c = Connectivity();
  bool online(List<ConnectivityResult> r) =>
      r.any((e) => e != ConnectivityResult.none);
  yield online(await c.checkConnectivity());
  yield* c.onConnectivityChanged.map(online);
}
