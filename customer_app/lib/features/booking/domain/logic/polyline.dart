import 'package:latlong2/latlong.dart';

import '../entities/booking_entities.dart';

extension PlaceInfoLatLng on PlaceInfo {
  LatLng toLatLng() => LatLng(lat, lng);
}

/// Decodes a Google-style encoded polyline (precision 5), the format the
/// backend returns for routes.
List<LatLng> decodePolyline(String encoded) {
  final points = <LatLng>[];
  var index = 0;
  var lat = 0;
  var lng = 0;
  int next() {
    var result = 0;
    var shift = 0;
    int b;
    do {
      b = encoded.codeUnitAt(index++) - 63;
      result |= (b & 0x1f) << shift;
      shift += 5;
    } while (b >= 0x20 && index < encoded.length);
    return (result & 1) != 0 ? ~(result >> 1) : (result >> 1);
  }

  try {
    while (index < encoded.length) {
      lat += next();
      lng += next();
      points.add(LatLng(lat / 1e5, lng / 1e5));
    }
  } on RangeError {
    // A truncated string yields the points decoded so far.
  }
  return points;
}
