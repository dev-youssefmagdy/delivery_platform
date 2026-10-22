import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import 'support_models.dart';

/// Help center and tickets for both apps (docs/API.md "Support").
abstract interface class SupportRepository {
  Future<List<TicketCategoryOption>> categories();

  /// [status]: `active`, `finished` or null for all.
  Future<TicketPage> tickets({int page = 1, String? status});

  Future<SupportTicket> ticket(String id);

  /// [key] is the Idempotency-Key of this intent; reuse it when retrying.
  Future<SupportTicket> open(NewTicketInput input, {required String key});

  Future<SupportTicket> reply(
    String ticketId, {
    required String key,
    required String body,
    List<String> attachmentPaths = const [],
  });

  /// Opens a ticket that disputes the cancellation fee of a booking.
  Future<SupportTicket> appeal(String bookingId, {required String key, required String reason});

  Future<Uint8List> loadImage(String url);
}

/// The only code that knows the support endpoints. [bookingPrefix] is
/// `/bookings` (customer) or `/provider/bookings` (driver), used by appeals.
class SupportRemoteDataSource {
  const SupportRemoteDataSource(this.api, {required this.bookingPrefix});

  final ApiClient api;
  final String bookingPrefix;

  Future<ApiResult<Object?>> categories() => api.get('/support/categories');

  Future<ApiResult<Object?>> tickets(int page, String? status) =>
      api.get('/support/tickets', query: {'page': page, 'status': ?status});

  Future<ApiResult<Object?>> ticket(String id) => api.get('/support/tickets/$id');

  Future<ApiResult<Object?>> open(NewTicketInput input, String key) async => api.post(
    '/support/tickets',
    idempotencyKey: key,
    body: FormData.fromMap({
      'category': input.category,
      'subject': input.subject,
      'body': input.body,
      if (input.bookingId != null) 'booking_id': input.bookingId,
      if (input.attachmentPaths.isNotEmpty)
        'attachments[]': [for (final p in input.attachmentPaths) await MultipartFile.fromFile(p)],
    }),
  );

  Future<ApiResult<Object?>> reply(String id, String key, String body, List<String> paths) async => api.post(
    '/support/tickets/$id/messages',
    idempotencyKey: key,
    body: FormData.fromMap({
      'body': body,
      if (paths.isNotEmpty) 'attachments[]': [for (final p in paths) await MultipartFile.fromFile(p)],
    }),
  );

  Future<ApiResult<Object?>> appeal(String bookingId, String key, String reason) => api.post(
    '$bookingPrefix/$bookingId/cancellation-appeal',
    idempotencyKey: key,
    body: {'reason': reason},
  );

  Future<Uint8List> bytes(String url) => api.getBytes(url);
}

class ApiSupportRepository implements SupportRepository {
  const ApiSupportRepository(this._remote);

  final SupportRemoteDataSource _remote;

  Map<String, dynamic> _map(ApiResult<Object?> r) {
    final data = r.data;
    if (data is! Map) {
      throw const ApiException(kind: ApiFailureKind.server, code: 'invalid_response', message: null);
    }
    return Map<String, dynamic>.from(data);
  }

  @override
  Future<List<TicketCategoryOption>> categories() async {
    final data = (await _remote.categories()).data;
    return [
      for (final item in (data is List ? data : const []))
        TicketCategoryOption.fromJson(Map<String, dynamic>.from(item as Map)),
    ];
  }

  @override
  Future<TicketPage> tickets({int page = 1, String? status}) async {
    final r = await _remote.tickets(page, status);
    final data = r.data;
    final pagination = r.meta['pagination'];
    return TicketPage(
      items: [
        for (final item in (data is List ? data : const []))
          SupportTicket.fromJson(Map<String, dynamic>.from(item as Map)),
      ],
      currentPage: pagination is Map ? (pagination['current_page'] as int? ?? page) : page,
      lastPage: pagination is Map ? (pagination['last_page'] as int? ?? page) : page,
    );
  }

  @override
  Future<SupportTicket> ticket(String id) async => SupportTicket.fromJson(_map(await _remote.ticket(id)));

  @override
  Future<SupportTicket> open(NewTicketInput input, {required String key}) async =>
      SupportTicket.fromJson(_map(await _remote.open(input, key)));

  @override
  Future<SupportTicket> reply(
    String ticketId, {
    required String key,
    required String body,
    List<String> attachmentPaths = const [],
  }) async => SupportTicket.fromJson(_map(await _remote.reply(ticketId, key, body, attachmentPaths)));

  @override
  Future<SupportTicket> appeal(String bookingId, {required String key, required String reason}) async =>
      SupportTicket.fromJson(_map(await _remote.appeal(bookingId, key, reason)));

  @override
  Future<Uint8List> loadImage(String url) => _remote.bytes(url);
}
