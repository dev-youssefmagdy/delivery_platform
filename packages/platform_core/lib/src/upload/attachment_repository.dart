import 'package:dio/dio.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';

/// An upload that no booking references yet (`POST /bookings/attachments`).
class UploadedAttachment {
  const UploadedAttachment({required this.id, required this.kind, this.mime, this.sizeBytes});

  final String id;
  final String kind;
  final String? mime;
  final int? sizeBytes;
}

enum AttachmentKind {
  photo('photo'),
  video('video'),
  document('document');

  const AttachmentKind(this.value);
  final String value;
}

/// Customer uploads for moving / freight (photos, videos, documents). The
/// form then references the returned ids (`service_details.photos[]`).
class BookingAttachmentRepository {
  BookingAttachmentRepository(this._api);

  final ApiClient _api;

  /// One Idempotency-Key per file path, kept until the upload succeeded or was
  /// refused for good, so a retry after a timeout never stores the file twice.
  final _keys = <String, String>{};

  Future<UploadedAttachment> upload(
    String path,
    AttachmentKind kind, {
    required String Function() newKey,
  }) async {
    final key = _keys.putIfAbsent('$path|${kind.value}', newKey);
    final r = await _api.post(
      '/bookings/attachments',
      idempotencyKey: key,
      body: FormData.fromMap({
        'kind': kind.value,
        'file': await MultipartFile.fromFile(path),
      }),
    );
    _keys.remove('$path|${kind.value}');
    final m = r.data is Map ? Map<String, dynamic>.from(r.data! as Map) : const <String, dynamic>{};
    final id = m['id'];
    if (id == null) {
      throw const ApiException(kind: ApiFailureKind.server, code: 'invalid_response', message: null);
    }
    return UploadedAttachment(
      id: '$id',
      kind: '${m['kind'] ?? kind.value}',
      mime: m['mime'] as String?,
      sizeBytes: (m['size_bytes'] as num?)?.toInt(),
    );
  }

  /// Forgets the kept key after a final refusal (a different file may reuse the path).
  void forget(String path, AttachmentKind kind) => _keys.remove('$path|${kind.value}');

  /// Removes an upload nothing references yet.
  Future<void> discard(String id) async {
    await _api.delete('/bookings/attachments/$id');
  }
}
