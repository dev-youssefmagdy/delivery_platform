import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import 'safety_models.dart';

/// Safety for both apps (docs/API.md "Safety").
abstract interface class SafetyRepository {
  /// Emergency numbers and button settings: the server's `/config/app` `safety` block. When the server cannot be reached the last
  /// good copy is returned; null only when there never was one (the UI then still offers the SOS and a plain call).
  Future<SafetyConfig?> config();

  /// Raises the SOS on an active job. No idempotency key: a repeated press joins the open case on the server.
  Future<SafetyIncident> sos({
    required String bookingId,
    SafetyFix? fix,
    String? category,
    bool notifyContacts = true,
  });

  /// Opens an incident report (needs a category and a text).
  Future<SafetyIncident> report({
    required String bookingId,
    required String category,
    required String narrative,
    SafetyFix? fix,
    String? callbackPhone,
  });

  Future<IncidentPage> incidents({int page = 1});

  Future<SafetyIncident> incident(String id);

  Future<SafetyIncident> addUpdate(
    String id, {
    required String key,
    required String body,
  });

  Future<void> addAttachment(
    String id, {
    required String key,
    required String path,
  });

  Future<Uint8List> loadFile(String url);

  Future<TrustedContactList> contacts();

  Future<TrustedContact> addContact(
    TrustedContactInput input, {
    required String key,
  });

  Future<TrustedContact> updateContact(String id, TrustedContactInput input);

  Future<void> removeContact(String id);

  Future<TripShareResult> shareTrip(String bookingId);

  Future<void> stopTripShare(String bookingId);
}

/// Last good copy of the safety configuration, kept on the device so the numbers show offline.
abstract interface class SafetyConfigCache {
  String? read();
  Future<void> write(String json);
}

/// The only code that knows the safety endpoints.
class SafetyRemoteDataSource {
  const SafetyRemoteDataSource(this.api);

  final ApiClient api;

  Future<ApiResult<Object?>> appConfig() => api.get('/config/app');

  Map<String, dynamic> _fix(SafetyFix? fix) => fix == null
      ? const {}
      : {
          'lat': fix.latitude,
          'lng': fix.longitude,
          'accuracy_m': ?fix.accuracyMeters,
          if (fix.capturedAt != null)
            'captured_at': fix.capturedAt!.toUtc().toIso8601String(),
        };

  Future<ApiResult<Object?>> sos(
    String bookingId,
    SafetyFix? fix,
    String? category,
    bool notify,
  ) => api.post(
    '/safety/incidents',
    body: {
      'booking_id': bookingId,
      'trigger': 'sos',
      'category': ?category,
      'notify_trusted_contacts': notify,
      ..._fix(fix),
    },
  );

  Future<ApiResult<Object?>> report(
    String bookingId,
    String category,
    String narrative,
    SafetyFix? fix,
    String? callback,
  ) => api.post(
    '/safety/incidents',
    body: {
      'booking_id': bookingId,
      'trigger': 'report',
      'category': category,
      'narrative': narrative,
      'callback_phone': ?callback,
      ..._fix(fix),
    },
  );

  Future<ApiResult<Object?>> incidents(int page) =>
      api.get('/safety/incidents', query: {'page': page});

  Future<ApiResult<Object?>> incident(String id) =>
      api.get('/safety/incidents/$id');

  Future<ApiResult<Object?>> addUpdate(String id, String key, String body) =>
      api.post(
        '/safety/incidents/$id/updates',
        idempotencyKey: key,
        body: {'body': body},
      );

  Future<ApiResult<Object?>> addAttachment(
    String id,
    String key,
    String path,
  ) async => api.post(
    '/safety/incidents/$id/attachments',
    idempotencyKey: key,
    body: FormData.fromMap({'file': await MultipartFile.fromFile(path)}),
  );

  Future<Uint8List> bytes(String url) => api.getBytes(url);

  Future<ApiResult<Object?>> contacts() => api.get('/safety/trusted-contacts');

  Future<ApiResult<Object?>> addContact(
    TrustedContactInput input,
    String key,
  ) => api.post(
    '/safety/trusted-contacts',
    idempotencyKey: key,
    body: input.toJson(),
  );

  Future<ApiResult<Object?>> updateContact(
    String id,
    TrustedContactInput input,
  ) => api.patch('/safety/trusted-contacts/$id', body: input.toJson());

  Future<ApiResult<Object?>> removeContact(String id) =>
      api.delete('/safety/trusted-contacts/$id');

  Future<ApiResult<Object?>> shareTrip(String bookingId) =>
      api.post('/safety/bookings/$bookingId/share');

  Future<ApiResult<Object?>> stopTripShare(String bookingId) =>
      api.delete('/safety/bookings/$bookingId/share');
}

class ApiSafetyRepository implements SafetyRepository {
  const ApiSafetyRepository(this._remote, {this.cache});

  final SafetyRemoteDataSource _remote;
  final SafetyConfigCache? cache;

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

  @override
  Future<SafetyConfig?> config() async {
    try {
      final data = _map(await _remote.appConfig())['safety'];
      if (data is Map) {
        final config = SafetyConfig.fromJson(Map<String, dynamic>.from(data));
        await cache?.write(jsonEncode(config.toJson()));
        return config;
      }
    } on ApiException {
      // Fall through to the stored copy: the numbers must show without a network.
    }
    final stored = cache?.read();
    if (stored == null) return null;
    try {
      return SafetyConfig.fromJson(
        Map<String, dynamic>.from(jsonDecode(stored) as Map),
      );
    } on Object {
      return null;
    }
  }

  @override
  Future<SafetyIncident> sos({
    required String bookingId,
    SafetyFix? fix,
    String? category,
    bool notifyContacts = true,
  }) async => SafetyIncident.fromJson(
    _map(await _remote.sos(bookingId, fix, category, notifyContacts)),
  );

  @override
  Future<SafetyIncident> report({
    required String bookingId,
    required String category,
    required String narrative,
    SafetyFix? fix,
    String? callbackPhone,
  }) async => SafetyIncident.fromJson(
    _map(
      await _remote.report(bookingId, category, narrative, fix, callbackPhone),
    ),
  );

  @override
  Future<IncidentPage> incidents({int page = 1}) async {
    final r = await _remote.incidents(page);
    final data = r.data;
    final pagination = r.meta['pagination'];
    return IncidentPage(
      items: [
        for (final item in (data is List ? data : const []))
          SafetyIncident.fromJson(Map<String, dynamic>.from(item as Map)),
      ],
      currentPage: pagination is Map
          ? (pagination['current_page'] as int? ?? page)
          : page,
      lastPage: pagination is Map
          ? (pagination['last_page'] as int? ?? page)
          : page,
    );
  }

  @override
  Future<SafetyIncident> incident(String id) async =>
      SafetyIncident.fromJson(_map(await _remote.incident(id)));

  @override
  Future<SafetyIncident> addUpdate(
    String id, {
    required String key,
    required String body,
  }) async =>
      SafetyIncident.fromJson(_map(await _remote.addUpdate(id, key, body)));

  @override
  Future<void> addAttachment(
    String id, {
    required String key,
    required String path,
  }) async {
    await _remote.addAttachment(id, key, path);
  }

  @override
  Future<Uint8List> loadFile(String url) => _remote.bytes(url);

  @override
  Future<TrustedContactList> contacts() async {
    final data = _map(await _remote.contacts());
    final items = data['items'];
    return TrustedContactList(
      items: [
        for (final item in (items is List ? items : const []))
          TrustedContact.fromJson(Map<String, dynamic>.from(item as Map)),
      ],
      max: (data['max'] as num?)?.toInt() ?? 5,
    );
  }

  @override
  Future<TrustedContact> addContact(
    TrustedContactInput input, {
    required String key,
  }) async =>
      TrustedContact.fromJson(_map(await _remote.addContact(input, key)));

  @override
  Future<TrustedContact> updateContact(
    String id,
    TrustedContactInput input,
  ) async =>
      TrustedContact.fromJson(_map(await _remote.updateContact(id, input)));

  @override
  Future<void> removeContact(String id) async {
    await _remote.removeContact(id);
  }

  @override
  Future<TripShareResult> shareTrip(String bookingId) async =>
      TripShareResult.fromJson(_map(await _remote.shareTrip(bookingId)));

  @override
  Future<void> stopTripShare(String bookingId) async {
    await _remote.stopTripShare(bookingId);
  }
}
