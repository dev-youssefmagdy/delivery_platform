import 'package:latlong2/latlong.dart';

/// What a place-search screen is picking.
enum PlaceSearchMode { pickup, destination, savedPlace }

class PlaceSearchArgs {
  const PlaceSearchArgs({required this.mode, this.near});

  final PlaceSearchMode mode;

  /// Ranking bias and the starting point of "choose on map".
  final LatLng? near;
}

class PinAdjustArgs {
  const PinAdjustArgs({required this.initial, this.checkServiceArea = true});

  final LatLng initial;

  /// Warn when the pin is outside every service zone.
  final bool checkServiceArea;
}
