import 'package:latlong2/latlong.dart';

import '../enums/location_precision.dart';
import '../enums/saved_place_type.dart';
import '../forms/form_schema.dart';

double? _double(Object? v) {
  if (v is num) return v.toDouble();
  return v is String ? double.tryParse(v) : null;
}

LatLng? _latLng(Object? lat, Object? lng) {
  final la = _double(lat);
  final ln = _double(lng);
  if (la == null || ln == null) return null;
  return LatLng(la, ln);
}

/// Result of place search / reverse geocoding (docs/API.md 8.7).
class PlaceResult {
  const PlaceResult({
    this.id,
    required this.title,
    required this.address,
    required this.point,
    required this.precision,
    this.provider,
  });

  static PlaceResult? tryFromJson(Map<String, dynamic> json) {
    final point = _latLng(json['lat'], json['lng']);
    if (point == null) return null;
    return PlaceResult(
      id: json['id'] as String?,
      title: '${json['title'] ?? ''}',
      address: '${json['address'] ?? ''}',
      point: point,
      precision: LocationPrecision.parse(json['precision'] as String?),
      provider: json['provider'] as String?,
    );
  }

  final String? id;
  final String title;
  final String address;
  final LatLng point;
  final LocationPrecision precision;
  final String? provider;

  /// Only exact results may be presented as a precise address.
  bool get isExact => precision == LocationPrecision.exact;
}

/// Why a category is (not) offered at a position right now.
class CategoryAvailabilityInfo {
  const CategoryAvailabilityInfo({
    required this.serviceCategoryId,
    required this.available,
    required this.reason,
  });

  factory CategoryAvailabilityInfo.fromJson(Map<String, dynamic> json) =>
      CategoryAvailabilityInfo(
        serviceCategoryId: (json['service_category_id'] as num?)?.toInt() ?? 0,
        available: json['available'] == true,
        reason: '${json['reason'] ?? ''}',
      );

  final int serviceCategoryId;
  final bool available;
  final String reason;
}

/// `GET /service-areas/resolve`. Outside every active zone [inServiceArea]
/// is false and the city/zone fields are empty.
class ServiceAreaInfo {
  const ServiceAreaInfo({
    required this.inServiceArea,
    this.cityId,
    this.cityName = const LocalizedText({}),
    this.zoneId,
    this.zoneName = const LocalizedText({}),
    this.restrictedPickup = false,
    this.categories = const [],
  });

  factory ServiceAreaInfo.fromJson(Map<String, dynamic> json) {
    final city = json['city'];
    final zone = json['zone'];
    return ServiceAreaInfo(
      inServiceArea: json['in_service_area'] == true,
      cityId: city is Map ? (city['id'] as num?)?.toInt() : null,
      cityName: LocalizedText.fromJson(city is Map ? city['name'] : null),
      zoneId: zone is Map ? (zone['id'] as num?)?.toInt() : null,
      zoneName: LocalizedText.fromJson(zone is Map ? zone['name'] : null),
      restrictedPickup: zone is Map && zone['restricted_pickup'] == true,
      categories: [
        for (final c in (json['categories'] as List?) ?? const [])
          if (c is Map)
            CategoryAvailabilityInfo.fromJson(Map<String, dynamic>.from(c)),
      ],
    );
  }

  final bool inServiceArea;
  final int? cityId;
  final LocalizedText cityName;
  final int? zoneId;
  final LocalizedText zoneName;
  final bool restrictedPickup;
  final List<CategoryAvailabilityInfo> categories;

  bool isCategoryAvailable(int categoryId) =>
      categories.any((c) => c.serviceCategoryId == categoryId && c.available);
}

/// A customer's saved place.
class SavedPlaceInfo {
  const SavedPlaceInfo({
    required this.id,
    required this.label,
    required this.type,
    required this.point,
    this.addressLine,
    this.precision = LocationPrecision.unknown,
  });

  static SavedPlaceInfo? tryFromJson(Map<String, dynamic> json) {
    final point = _latLng(json['lat'], json['lng']);
    final id = json['id'];
    if (point == null || id is! String) return null;
    final address = json['address'];
    return SavedPlaceInfo(
      id: id,
      label: '${json['label'] ?? ''}',
      type: SavedPlaceType.parse(json['type'] as String?),
      point: point,
      addressLine: address is Map ? address['line'] as String? : null,
      precision: LocationPrecision.parse(
        address is Map ? address['precision'] as String? : null,
      ),
    );
  }

  final String id;
  final String label;
  final SavedPlaceType type;
  final LatLng point;
  final String? addressLine;
  final LocationPrecision precision;
}

/// `GET /routes/estimate`: distance, time and the encoded polyline only.
class RouteEstimateInfo {
  const RouteEstimateInfo({
    required this.distanceMeters,
    required this.durationSeconds,
    required this.polyline,
  });

  factory RouteEstimateInfo.fromJson(Map<String, dynamic> json) =>
      RouteEstimateInfo(
        distanceMeters: (json['distance_m'] as num?)?.toInt() ?? 0,
        durationSeconds: (json['duration_s'] as num?)?.toInt() ?? 0,
        polyline: '${json['polyline'] ?? ''}',
      );

  final int distanceMeters;
  final int durationSeconds;
  final String polyline;
}
