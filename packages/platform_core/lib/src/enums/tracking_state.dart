import 'api_enum.dart';

/// Mirrors backend `TrackingState`: how closely the server wants the driver's
/// position right now. The app picks its sampling rate from the interval the
/// server sends with it; unknown wire values map to [TrackingState.off].
enum TrackingState implements ApiEnum {
  off('off'),
  idle('idle'),
  toPickup('to_pickup'),
  active('active');

  const TrackingState(this.value);

  @override
  final String value;

  bool get isTracking => this != off;

  static TrackingState parse(String? raw) =>
      parseApiEnum(TrackingState.values, raw, TrackingState.off);
}
