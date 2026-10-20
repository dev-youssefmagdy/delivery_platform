import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../core/providers.dart';
import '../../domain/place_selection.dart';

/// Where maps open before anything is known (downtown Cairo). Real deployments
/// open on the user's position or the pickup instead.
const defaultMapCenter = LatLng(30.0444, 31.2357);

final locationServiceProvider = Provider<LocationService>(
  (ref) => const GeolocatorLocationService(),
);

final geoRepositoryProvider = Provider<GeoRepository>(
  (ref) => GeoRepository(ref.watch(apiClientProvider)),
);

/// Tile source for every map. Tests override it with a provider that never
/// touches the network.
final mapTileProviderProvider = Provider<TileProvider>(
  (ref) => NetworkTileProvider(),
);

/// Where the device is, as far as we know. [permission] null = not checked yet.
class UserLocationState {
  const UserLocationState({
    this.permission,
    this.point,
    this.busy = false,
    this.failed = false,
  });

  final LocationPermissionState? permission;
  final LatLng? point;
  final bool busy;

  /// Permission was granted but no position could be read (no GPS fix, timeout).
  final bool failed;

  bool get granted => permission == LocationPermissionState.granted;

  UserLocationState copyWith({
    LocationPermissionState? permission,
    LatLng? point,
    bool? busy,
    bool? failed,
  }) => UserLocationState(
    permission: permission ?? this.permission,
    point: point ?? this.point,
    busy: busy ?? this.busy,
    failed: failed ?? this.failed,
  );
}

/// Device location with the rationale-first permission flow (Plan §6.1).
/// Never prompts on its own: [refresh] only reads the current permission,
/// [enable] asks after the caller showed the rationale.
class UserLocationNotifier extends Notifier<UserLocationState> {
  @override
  UserLocationState build() => const UserLocationState();

  LocationService get _service => ref.read(locationServiceProvider);

  /// Reads the permission silently and, when granted, the current position.
  Future<void> refresh() async {
    final permission = await _service.status();
    state = UserLocationState(permission: permission, point: state.point);
    if (permission == LocationPermissionState.granted) await _locate();
  }

  /// Runs the permission flow: [showRationale] must explain why location is
  /// needed and return true when the user agrees to the OS prompt.
  Future<void> enable({required Future<bool> Function() showRationale}) async {
    final permission = await LocationPermissionFlow(_service)
        .ensure(showRationale: showRationale);
    state = UserLocationState(permission: permission, point: state.point);
    if (permission == LocationPermissionState.granted) await _locate();
  }

  Future<void> locate() async {
    if (!state.granted) return refresh();
    await _locate();
  }

  Future<void> _locate() async {
    state = state.copyWith(busy: true, failed: false);
    try {
      final fix = await _service.current();
      state = fix == null
          ? state.copyWith(busy: false, failed: true)
          : UserLocationState(
              permission: state.permission,
              point: LatLng(fix.latitude, fix.longitude),
            );
    } on Object {
      state = state.copyWith(busy: false, failed: true);
    }
  }

  Future<void> openSettings() =>
      state.permission == LocationPermissionState.serviceDisabled
      ? _service.openLocationSettings()
      : _service.openAppSettings();
}

final userLocationProvider =
    NotifierProvider<UserLocationNotifier, UserLocationState>(
      UserLocationNotifier.new,
    );

class TripEndpointsNotifier extends Notifier<TripEndpoints> {
  @override
  TripEndpoints build() => const TripEndpoints();

  void setPickup(PlaceSelection pickup) =>
      state = TripEndpoints(pickup: pickup, destination: state.destination);

  void setDestination(PlaceSelection destination) =>
      state = TripEndpoints(pickup: state.pickup, destination: destination);

  void clearDestination() => state = TripEndpoints(pickup: state.pickup);

  void swap() {
    if (state.canSwap) state = state.swapped();
  }
}

final tripEndpointsProvider =
    NotifierProvider<TripEndpointsNotifier, TripEndpoints>(
      TripEndpointsNotifier.new,
    );

/// The pickup position rounded to ~10 m, so tiny GPS jitter does not refetch
/// the service area and the catalog.
final pickupPointProvider = Provider<LatLng?>((ref) {
  final point = ref.watch(tripEndpointsProvider.select((t) => t.pickup?.point));
  if (point == null) return null;
  double r(double v) => (v * 10000).round() / 10000;
  return LatLng(r(point.latitude), r(point.longitude));
});

Duration? _noAutoRetry(int retryCount, Object error) => null;

/// Service area of the pickup. Null while there is no pickup.
final serviceAreaProvider = FutureProvider<ServiceAreaInfo?>((ref) async {
  final pickup = ref.watch(pickupPointProvider);
  if (pickup == null) return null;
  return ref.watch(geoRepositoryProvider).resolveArea(pickup);
}, retry: _noAutoRetry);

/// Saved places of the signed-in customer.
class SavedPlacesNotifier extends AsyncNotifier<List<SavedPlaceInfo>> {
  @override
  Future<List<SavedPlaceInfo>> build() =>
      ref.watch(geoRepositoryProvider).savedPlaces();

  GeoRepository get _repo => ref.read(geoRepositoryProvider);

  Future<void> add({
    required String label,
    required SavedPlaceType type,
    required PlaceSelection selection,
  }) async {
    final created = await _repo.createSavedPlace(
      label: label,
      type: type,
      point: selection.point,
      place: selection.place,
    );
    state = AsyncData(_sorted([...?state.value, created]));
  }

  Future<void> remove(String id) async {
    await _repo.deleteSavedPlace(id);
    state = AsyncData([
      for (final p in state.value ?? const <SavedPlaceInfo>[])
        if (p.id != id) p,
    ]);
  }

  List<SavedPlaceInfo> _sorted(List<SavedPlaceInfo> places) =>
      [...places]..sort((a, b) => a.type.index.compareTo(b.type.index));
}

final savedPlacesProvider =
    AsyncNotifierProvider<SavedPlacesNotifier, List<SavedPlaceInfo>>(
      SavedPlacesNotifier.new,
      retry: _noAutoRetry,
    );
