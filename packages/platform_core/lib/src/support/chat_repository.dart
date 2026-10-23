import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/api_result.dart';
import 'support_models.dart';

/// Booking chat for one app (docs/API.md "Chat"). Screens depend on this
/// contract; [ApiChatRepository] implements it over [ChatRemoteDataSource].
abstract interface class ChatRepository {
  /// The latest page, or messages newer than [afterId] / older than [beforeId].
  Future<ChatSnapshot> messages(
    String bookingId, {
    String? afterId,
    String? beforeId,
  });

  /// Sends text and/or one image. [clientKey] is the Idempotency-Key: reuse it
  /// when retrying the same message so it is stored once.
  Future<ChatMessage> send(
    String bookingId, {
    required String clientKey,
    String? body,
    String? imagePath,
  });

  /// Read receipt for the other side's messages.
  Future<void> markRead(String bookingId, {String? upToId});

  /// Downloads a private image through its signed URL.
  Future<Uint8List> loadImage(String url);
}

/// The only code that knows the chat endpoints. [prefix] is `/bookings` in the
/// customer app and `/provider/bookings` in the driver app.
class ChatRemoteDataSource {
  const ChatRemoteDataSource(this.api, {required this.prefix});

  final ApiClient api;
  final String prefix;

  Future<ApiResult<Object?>> messages(
    String bookingId,
    String? afterId,
    String? beforeId,
  ) => api.get(
    '$prefix/$bookingId/messages',
    query: {'after': ?afterId, 'before': ?beforeId},
  );

  Future<ApiResult<Object?>> send(
    String bookingId,
    String key,
    String? body,
    String? imagePath,
  ) async => api.post(
    '$prefix/$bookingId/messages',
    idempotencyKey: key,
    body: FormData.fromMap({
      if (body != null && body.isNotEmpty) 'body': body,
      if (imagePath != null)
        'attachment': await MultipartFile.fromFile(imagePath),
    }),
  );

  Future<ApiResult<Object?>> markRead(String bookingId, String? upToId) =>
      api.post('$prefix/$bookingId/messages/read', body: {'up_to': ?upToId});

  Future<Uint8List> bytes(String url) => api.getBytes(url);
}

class ApiChatRepository implements ChatRepository {
  const ApiChatRepository(this._remote);

  final ChatRemoteDataSource _remote;

  @override
  Future<ChatSnapshot> messages(
    String bookingId, {
    String? afterId,
    String? beforeId,
  }) async {
    final r = await _remote.messages(bookingId, afterId, beforeId);
    final data = r.data;
    return ChatSnapshot(
      messages: [
        for (final item in (data is List ? data : const []))
          ChatMessage.fromJson(Map<String, dynamic>.from(item as Map)),
      ],
      role: ChatRole.parse(r.meta['role'] as String?),
      canSend: r.meta['can_send'] as bool? ?? false,
      unread: r.meta['unread'] as int? ?? 0,
      hasMore: r.meta['has_more'] as bool? ?? false,
      maxChars: r.meta['max_chars'] as int? ?? 1000,
      closesAt: r.meta['closes_at'] is String
          ? DateTime.tryParse(r.meta['closes_at'] as String)?.toLocal()
          : null,
    );
  }

  @override
  Future<ChatMessage> send(
    String bookingId, {
    required String clientKey,
    String? body,
    String? imagePath,
  }) async {
    final r = await _remote.send(bookingId, clientKey, body, imagePath);
    final data = r.data;
    if (data is! Map) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return ChatMessage.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<void> markRead(String bookingId, {String? upToId}) async {
    await _remote.markRead(bookingId, upToId);
  }

  @override
  Future<Uint8List> loadImage(String url) => _remote.bytes(url);
}
