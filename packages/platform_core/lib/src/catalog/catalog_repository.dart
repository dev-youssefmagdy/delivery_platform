import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import '../enums/booking_type.dart';
import 'catalog_models.dart';

/// Service catalog API (docs/API.md 8.6) for both apps. Only code that knows
/// these endpoints; throws [ApiException].
class CatalogRepository {
  const CatalogRepository(this.api);

  final ApiClient api;

  Future<List<ServiceFamilyInfo>> families() async =>
      _list(await api.get('/service-families'), ServiceFamilyInfo.fromJson);

  /// With [lat]/[lng] the server returns only the categories the serving zone
  /// offers right now (empty outside every service zone).
  Future<List<ServiceCategoryInfo>> categories({
    BookingType? bookingType,
    int? familyId,
    double? lat,
    double? lng,
  }) async => _list(
    await api.get(
      '/service-categories',
      query: {
        'booking_type': ?bookingType?.value,
        'service_family_id': ?familyId,
        'lat': ?lat,
        'lng': ?lng,
      },
    ),
    ServiceCategoryInfo.fromJson,
  );

  Future<List<VehicleCategoryInfo>> vehicleCategories({
    int? serviceCategoryId,
  }) async => _list(
    await api.get(
      '/vehicle-categories',
      query: {'service_category_id': ?serviceCategoryId},
    ),
    VehicleCategoryInfo.fromJson,
  );

  List<T> _list<T>(
    ApiResult<Object?> result,
    T Function(Map<String, dynamic>) parse,
  ) {
    final data = result.data;
    if (data is! List) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return [
      for (final item in data)
        if (item is Map) parse(Map<String, dynamic>.from(item)),
    ];
  }
}
