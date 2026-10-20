import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

/// A pickup/destination choice: a position plus whatever the geocoder told us
/// about it. [precision] decides whether [address] may be shown as an address
/// (SRS §6.3: never pretend an approximate position is an exact address).
class PlaceSelection {
  const PlaceSelection({
    required this.point,
    this.title,
    this.address,
    this.precision = LocationPrecision.unknown,
    this.isCurrentLocation = false,
    this.place,
  });

  factory PlaceSelection.fromPlace(PlaceResult place) => PlaceSelection(
    point: place.point,
    title: place.title,
    address: place.address,
    precision: place.precision,
    place: place,
  );

  factory PlaceSelection.fromSaved(SavedPlaceInfo saved) => PlaceSelection(
    point: saved.point,
    title: saved.label,
    address: saved.addressLine,
    precision: saved.precision,
  );

  final LatLng point;
  final String? title;
  final String? address;
  final LocationPrecision precision;
  final bool isCurrentLocation;

  /// The geocoder answer this came from, kept for saving as a place.
  final PlaceResult? place;

  bool get hasExactAddress => precision == LocationPrecision.exact;

  PlaceSelection copyWith({
    LatLng? point,
    String? title,
    String? address,
    LocationPrecision? precision,
    bool? isCurrentLocation,
    PlaceResult? place,
  }) => PlaceSelection(
    point: point ?? this.point,
    title: title ?? this.title,
    address: address ?? this.address,
    precision: precision ?? this.precision,
    isCurrentLocation: isCurrentLocation ?? this.isCurrentLocation,
    place: place ?? this.place,
  );
}

/// The two ends of the trip being composed on the home map.
class TripEndpoints {
  const TripEndpoints({this.pickup, this.destination});

  final PlaceSelection? pickup;
  final PlaceSelection? destination;

  bool get canSwap => pickup != null && destination != null;

  TripEndpoints swapped() =>
      TripEndpoints(pickup: destination, destination: pickup);
}
