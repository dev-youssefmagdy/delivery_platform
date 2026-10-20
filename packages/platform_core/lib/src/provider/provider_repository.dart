import 'package:dio/dio.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import '../enums/provider_type.dart';
import '../enums/vehicle_photo_kind.dart';
import 'provider_models.dart';

/// Provider onboarding API (docs/API.md 8.9): application, vehicles, documents
/// and eligibility. The only code that knows these endpoints; throws
/// [ApiException]. Creating writes take an idempotency key generated once per
/// user intent and kept across retries.
class ProviderRepository {
  const ProviderRepository(this.api);

  final ApiClient api;

  Map<String, dynamic> _map(ApiResult<Object?> r) {
    final data = r.data;
    if (data is! Map) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return Map<String, dynamic>.from(data);
  }

  List<Map<String, dynamic>> _list(ApiResult<Object?> r) {
    final data = r.data;
    if (data is! List) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return [
      for (final item in data)
        if (item is Map) Map<String, dynamic>.from(item),
    ];
  }

  Future<List<CityInfo>> cities() async =>
      _list(await api.get('/cities')).map(CityInfo.fromJson).toList();

  Future<ProviderApplication> application() async =>
      ProviderApplication.fromJson(
        _map(await api.get('/provider/application')),
      );

  Future<ProviderApplication> startApplication({
    required ProviderType providerType,
    required int cityId,
    required String name,
    required String idempotencyKey,
    DateTime? dateOfBirth,
    List<int> serviceCategoryIds = const [],
  }) async => ProviderApplication.fromJson(
    _map(
      await api.post(
        '/provider/applications',
        body: {
          'provider_type': providerType.value,
          'city_id': cityId,
          'name': name,
          'date_of_birth': ?_day(dateOfBirth),
          'service_category_ids': serviceCategoryIds,
        },
        idempotencyKey: idempotencyKey,
      ),
    ),
  );

  /// Saves any subset of the application fields; null fields are unchanged.
  Future<ProviderApplication> updateApplication({
    ProviderType? providerType,
    int? cityId,
    String? name,
    DateTime? dateOfBirth,
    List<int>? serviceCategoryIds,
  }) async => ProviderApplication.fromJson(
    _map(
      await api.patch(
        '/provider/application',
        body: {
          'provider_type': ?providerType?.value,
          'city_id': ?cityId,
          'name': ?name,
          'date_of_birth': ?_day(dateOfBirth),
          'service_category_ids': ?serviceCategoryIds,
        },
      ),
    ),
  );

  Future<ProviderApplication> submitApplication({
    required String idempotencyKey,
  }) async => ProviderApplication.fromJson(
    _map(
      await api.post(
        '/provider/application/submit',
        body: const <String, dynamic>{},
        idempotencyKey: idempotencyKey,
      ),
    ),
  );

  Future<EligibilityInfo> eligibility() async =>
      EligibilityInfo.fromJson(_map(await api.get('/provider/eligibility')));

  Future<ProviderVehicleInfo> createVehicle(
    VehicleDraft draft, {
    required String idempotencyKey,
  }) async => ProviderVehicleInfo.fromJson(
    _map(
      await api.post(
        '/provider/vehicles',
        body: draft.toJson(),
        idempotencyKey: idempotencyKey,
      ),
    ),
  );

  Future<ProviderVehicleInfo> updateVehicle(
    String vehicleId,
    VehicleDraft draft,
  ) async => ProviderVehicleInfo.fromJson(
    _map(
      await api.patch('/provider/vehicles/$vehicleId', body: draft.toJson()),
    ),
  );

  Future<void> deleteVehicle(String vehicleId) async {
    await api.delete('/provider/vehicles/$vehicleId');
  }

  Future<ProviderVehicleInfo> addVehiclePhoto(
    String vehicleId, {
    required String filePath,
    required VehiclePhotoKind kind,
    required String idempotencyKey,
  }) async => ProviderVehicleInfo.fromJson(
    _map(
      await api.post(
        '/provider/vehicles/$vehicleId/photos',
        body: FormData.fromMap({
          'kind': kind.value,
          'photo': await MultipartFile.fromFile(filePath),
        }),
        idempotencyKey: idempotencyKey,
      ),
    ),
  );

  Future<ProviderVehicleInfo> removeVehiclePhoto(
    String vehicleId,
    String photoId,
  ) async => ProviderVehicleInfo.fromJson(
    _map(await api.delete('/provider/vehicles/$vehicleId/photos/$photoId')),
  );

  Future<ProviderVehicleInfo> submitVehicle(
    String vehicleId, {
    required String idempotencyKey,
  }) async => ProviderVehicleInfo.fromJson(
    _map(
      await api.post(
        '/provider/vehicles/$vehicleId/submit-review',
        body: const <String, dynamic>{},
        idempotencyKey: idempotencyKey,
      ),
    ),
  );

  Future<List<ProviderDocumentInfo>> documents() async =>
      _list(await api.get('/provider/documents'))
          .map(ProviderDocumentInfo.fromJson)
          .toList();

  /// Uploads one document (multipart). Re-send with the same
  /// [idempotencyKey] when the network failed so the server stores it once.
  Future<ProviderDocumentInfo> uploadDocument({
    required String documentType,
    required String filePath,
    required String idempotencyKey,
    String? number,
    DateTime? issuedAt,
    DateTime? expiresAt,
    String? vehicleId,
  }) async => ProviderDocumentInfo.fromJson(
    _map(
      await api.post(
        '/provider/documents',
        body: FormData.fromMap({
          'document_type': documentType,
          'file': await MultipartFile.fromFile(filePath),
          'number': ?_nonEmpty(number),
          'issued_at': ?_day(issuedAt),
          'expires_at': ?_day(expiresAt),
          'vehicle_id': ?vehicleId,
        }),
        idempotencyKey: idempotencyKey,
      ),
    ),
  );

  static String? _nonEmpty(String? value) =>
      value == null || value.trim().isEmpty ? null : value.trim();

  static String? _day(DateTime? date) {
    if (date == null) return null;
    final y = date.year.toString().padLeft(4, '0');
    final m = date.month.toString().padLeft(2, '0');
    final d = date.day.toString().padLeft(2, '0');
    return '$y-$m-$d';
  }
}
