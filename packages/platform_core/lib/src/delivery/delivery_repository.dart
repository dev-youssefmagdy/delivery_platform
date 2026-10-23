import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../api/api_client.dart';
import '../enums/proof_type.dart';
import 'delivery_models.dart';

List<Map<String, dynamic>> _maps(Object? v) => [
  if (v is List)
    for (final e in v)
      if (e is Map) Map<String, dynamic>.from(e),
];

/// One checklist answer of a checklist proof.
class ChecklistAnswer {
  const ChecklistAnswer({required this.code, required this.checked});

  final String code;
  final bool checked;
}

/// What the provider records as proof.
class ProofInput {
  const ProofInput({
    required this.type,
    this.filePath,
    this.checklist = const [],
    this.weightKg,
    this.note,
  });

  final ProofType type;

  /// Required for `photo` and `signature`.
  final String? filePath;
  final List<ChecklistAnswer> checklist;
  final double? weightKg;
  final String? note;
}

/// `{proof, booking}` of a proof upload; [booking] is the raw booking `data`
/// (the app maps it with its own DTO), [replayed] when the same picture was
/// already stored.
class ProofResult {
  const ProofResult({this.proof, this.booking, this.replayed = false});

  final BookingProof? proof;
  final Object? booking;
  final bool replayed;
}

/// A failed attempt recorded: the attempt and the booking in
/// `delivery_attempt_failed`.
class AttemptResult {
  const AttemptResult({this.attempt, this.booking, this.replayed = false});

  final DeliveryAttempt? attempt;
  final Object? booking;
  final bool replayed;
}

/// Proofs, custody, recipient code and failed attempts, for both apps. The
/// customer app uses `/bookings` and only reads proofs / resolves attempts;
/// the driver app uses `/provider/bookings` and does the field work. Every
/// write takes the Idempotency-Key of its user intent.
class DeliveryRepository {
  const DeliveryRepository(this._api, {required this.bookingPrefix});

  final ApiClient _api;

  /// `/bookings` (customer) or `/provider/bookings` (driver).
  final String bookingPrefix;

  String _b(String id) => '$bookingPrefix/$id';

  Future<List<BookingProof>> proofs(String bookingId) async => [
    for (final e in _maps((await _api.get('${_b(bookingId)}/proofs')).data))
      ?BookingProof.fromJson(e),
  ];

  /// A picture behind a signed URL, downloaded with the app's auth.
  Future<Uint8List> fileBytes(String url) => _api.getBytes(url);

  Future<List<AttachmentInfo>> attachments(String bookingId) async => [
    for (final e in _maps(
      (await _api.get('${_b(bookingId)}/attachments')).data,
    ))
      ?AttachmentInfo.fromJson(e),
  ];

  /// Records a proof (driver). The body is rebuilt on every call so a retry
  /// with the same [key] re-sends the same picture.
  Future<ProofResult> uploadProof(
    String bookingId,
    ProofInput input, {
    required String key,
  }) async {
    final Object body;
    if (input.type == ProofType.photo || input.type == ProofType.signature) {
      body = FormData.fromMap({
        'type': input.type.value,
        'file': await MultipartFile.fromFile(input.filePath!),
        if (input.note != null && input.note!.trim().isNotEmpty)
          'note': input.note!.trim(),
      });
    } else {
      body = {
        'type': input.type.value,
        if (input.type == ProofType.checklist)
          'items': [
            for (final a in input.checklist)
              {'code': a.code, 'checked': a.checked},
          ],
        if (input.type == ProofType.weight) 'weight_kg': input.weightKg,
        if (input.note != null && input.note!.trim().isNotEmpty)
          'note': input.note!.trim(),
      };
    }
    final r = await _api.post(
      '${_b(bookingId)}/proofs',
      body: body,
      idempotencyKey: key,
    );
    final data = r.data is Map
        ? Map<String, dynamic>.from(r.data! as Map)
        : const <String, dynamic>{};
    return ProofResult(
      proof: BookingProof.fromJson(data['proof']),
      booking: data['booking'],
      replayed: r.meta['replayed'] == true,
    );
  }

  /// `in_transit` or `arrived_dropoff` (driver). Returns the booking `data`.
  Future<Object?> custody(
    String bookingId,
    CustodyStatus event, {
    required String key,
  }) async => (await _api.post(
    '${_b(bookingId)}/custody',
    body: {'event': event.value},
    idempotencyKey: key,
  )).data;

  /// Enters the code the recipient read out (driver). Returns the booking.
  Future<Object?> verifyRecipientOtp(
    String bookingId,
    String code, {
    required String key,
  }) async => (await _api.post(
    '${_b(bookingId)}/verify-recipient-otp',
    body: {'code': code},
    idempotencyKey: key,
  )).data;

  Future<Object?> resendRecipientOtp(
    String bookingId, {
    required String key,
  }) async => (await _api.post(
    '${_b(bookingId)}/recipient-otp/resend',
    idempotencyKey: key,
  )).data;

  /// Reports a failed attempt with an optional evidence picture (driver).
  Future<AttemptResult> reportAttempt(
    String bookingId, {
    required AttemptOutcome outcome,
    String? reason,
    String? evidencePath,
    required String key,
  }) async {
    final Object body = evidencePath == null
        ? {
            'outcome': outcome.value,
            if (reason != null && reason.trim().isNotEmpty)
              'reason': reason.trim(),
          }
        : FormData.fromMap({
            'outcome': outcome.value,
            if (reason != null && reason.trim().isNotEmpty)
              'reason': reason.trim(),
            'evidence': await MultipartFile.fromFile(evidencePath),
          });
    final r = await _api.post(
      '${_b(bookingId)}/delivery-attempts',
      body: body,
      idempotencyKey: key,
    );
    final data = r.data is Map
        ? Map<String, dynamic>.from(r.data! as Map)
        : const <String, dynamic>{};
    return AttemptResult(
      attempt: DeliveryAttempt.fromJson(data['attempt']),
      booking: data['booking'],
      replayed: r.meta['replayed'] == true,
    );
  }

  /// Retry / return to sender / hand to operations (both apps). Returns the
  /// booking `data`.
  Future<Object?> resolveAttempt(
    String bookingId,
    String attemptId,
    AttemptResolution resolution, {
    required String key,
  }) async => (await _api.post(
    '${_b(bookingId)}/delivery-attempts/$attemptId/resolve',
    body: {'resolution': resolution.value},
    idempotencyKey: key,
  )).data;
}
