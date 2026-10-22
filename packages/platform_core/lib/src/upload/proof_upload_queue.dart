import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';

import '../api/api_exception.dart';
import '../delivery/delivery_repository.dart';
import '../enums/proof_type.dart';

/// Where the queue keeps its items between app starts (the apps back it with
/// SharedPreferences; tests with a list).
abstract interface class UploadQueueStore {
  List<String> read();

  Future<void> write(List<String> items);
}

enum PendingUploadState {
  /// Waiting for a connection or the next retry time.
  pending,

  /// Refused for good (a rule, not the network): needs the driver's decision.
  failed,
}

/// One proof waiting to be uploaded. The Idempotency-Key is fixed when the
/// proof is captured, so every retry (also after an app restart) sends the same
/// key and the server stores the proof once.
class PendingUpload {
  const PendingUpload({
    required this.id,
    required this.bookingId,
    required this.type,
    required this.idempotencyKey,
    required this.createdAt,
    this.filePath,
    this.note,
    this.checklist = const [],
    this.weightKg,
    this.attempts = 0,
    this.nextAttemptAt,
    this.state = PendingUploadState.pending,
    this.errorCode,
  });

  factory PendingUpload.fromJson(Map<String, dynamic> json) => PendingUpload(
    id: '${json['id']}',
    bookingId: '${json['booking_id']}',
    type: ProofType.parse(json['type'] as String?),
    idempotencyKey: '${json['key']}',
    createdAt: DateTime.tryParse('${json['created_at']}') ?? DateTime.now(),
    filePath: json['file_path'] as String?,
    note: json['note'] as String?,
    checklist: [
      if (json['checklist'] is List)
        for (final e in json['checklist'] as List)
          if (e is Map)
            ChecklistAnswer(code: '${e['code']}', checked: e['checked'] == true),
    ],
    weightKg: (json['weight_kg'] as num?)?.toDouble(),
    attempts: (json['attempts'] as num?)?.toInt() ?? 0,
    nextAttemptAt: DateTime.tryParse('${json['next_attempt_at']}'),
    state: json['state'] == 'failed'
        ? PendingUploadState.failed
        : PendingUploadState.pending,
    errorCode: json['error_code'] as String?,
  );

  final String id;
  final String bookingId;
  final ProofType type;
  final String idempotencyKey;
  final DateTime createdAt;
  final String? filePath;
  final String? note;
  final List<ChecklistAnswer> checklist;
  final double? weightKg;
  final int attempts;
  final DateTime? nextAttemptAt;
  final PendingUploadState state;
  final String? errorCode;

  PendingUpload copyWith({
    int? attempts,
    DateTime? nextAttemptAt,
    PendingUploadState? state,
    String? errorCode,
    bool clearError = false,
  }) => PendingUpload(
    id: id,
    bookingId: bookingId,
    type: type,
    idempotencyKey: idempotencyKey,
    createdAt: createdAt,
    filePath: filePath,
    note: note,
    checklist: checklist,
    weightKg: weightKg,
    attempts: attempts ?? this.attempts,
    nextAttemptAt: nextAttemptAt ?? this.nextAttemptAt,
    state: state ?? this.state,
    errorCode: clearError ? null : (errorCode ?? this.errorCode),
  );

  ProofInput toInput() => ProofInput(
    type: type,
    filePath: filePath,
    checklist: checklist,
    weightKg: weightKg,
    note: note,
  );

  Map<String, Object?> toJson() => {
    'id': id,
    'booking_id': bookingId,
    'type': type.value,
    'key': idempotencyKey,
    'created_at': createdAt.toUtc().toIso8601String(),
    'file_path': filePath,
    'note': note,
    'checklist': [
      for (final a in checklist) {'code': a.code, 'checked': a.checked},
    ],
    'weight_kg': weightKg,
    'attempts': attempts,
    'next_attempt_at': nextAttemptAt?.toUtc().toIso8601String(),
    'state': state == PendingUploadState.failed ? 'failed' : 'pending',
    'error_code': errorCode,
  };
}

/// Sends one proof; throws [ApiException] on failure.
typedef ProofSender = Future<ProofResult> Function(PendingUpload item);

/// True when the same request must be sent again later (the network, a busy
/// server, a throttle): the proof stays queued.
bool isTransientUploadFailure(ApiException e) =>
    e.isOffline ||
    e.kind == ApiFailureKind.server ||
    e.kind == ApiFailureKind.rateLimited ||
    e.code == 'idempotency_request_in_progress' ||
    e.code == 'file_scanner_unavailable';

/// Offline-safe proof uploads for the driver (SRS 7.5): a captured proof is
/// stored first, then sent; a failure because of the network keeps it queued
/// with an exponential back-off and the SAME Idempotency-Key. A refusal by a
/// rule moves it to `failed` so the driver can discard it or try again. A
/// 401 stops the run and keeps everything (the session screen takes over).
class ProofUploadQueue extends ChangeNotifier {
  ProofUploadQueue({
    required UploadQueueStore store,
    required ProofSender sender,
    DateTime Function()? now,
    this.baseDelay = const Duration(seconds: 5),
    this.maxDelay = const Duration(minutes: 5),
    this.onUploaded,
  }) : _store = store,
       _sender = sender,
       _now = now ?? DateTime.now {
    for (final raw in store.read()) {
      try {
        final decoded = jsonDecode(raw);
        if (decoded is Map) {
          _items.add(PendingUpload.fromJson(Map<String, dynamic>.from(decoded)));
        }
      } on FormatException {
        // A corrupt entry is dropped; nothing else depends on it.
      }
    }
  }

  final UploadQueueStore _store;
  final ProofSender _sender;
  final DateTime Function() _now;
  final Duration baseDelay;
  final Duration maxDelay;

  /// Called after a proof was stored (the app refreshes the booking).
  final void Function(PendingUpload item, ProofResult? result)? onUploaded;

  final _items = <PendingUpload>[];
  bool _flushing = false;

  List<PendingUpload> get items => List.unmodifiable(_items);

  List<PendingUpload> forBooking(String bookingId) => [
    for (final i in _items)
      if (i.bookingId == bookingId) i,
  ];

  bool get isFlushing => _flushing;

  /// Delay before retry number [attempts] (1-based): 5 s, 10 s, 20 s ... capped.
  Duration backoff(int attempts) {
    final factor = 1 << (attempts - 1).clamp(0, 20);
    final delay = baseDelay * factor;
    return delay > maxDelay ? maxDelay : delay;
  }

  Future<void> _persist() =>
      _store.write([for (final i in _items) jsonEncode(i.toJson())]);

  /// Stores a captured proof and tries to send it right away.
  Future<PendingUpload> enqueue({
    required String bookingId,
    required ProofInput input,
    required String Function() newKey,
    String? id,
  }) async {
    final item = PendingUpload(
      id: id ?? newKey(),
      bookingId: bookingId,
      type: input.type,
      idempotencyKey: newKey(),
      createdAt: _now(),
      filePath: input.filePath,
      note: input.note,
      checklist: input.checklist,
      weightKg: input.weightKg,
    );
    _items.add(item);
    await _persist();
    notifyListeners();
    unawaited(flush());
    return item;
  }

  void _replace(PendingUpload item) {
    final index = _items.indexWhere((i) => i.id == item.id);
    if (index >= 0) _items[index] = item;
  }

  /// Sends every due proof, oldest first, one at a time. [force] ignores the
  /// back-off (retry button, connection came back).
  Future<void> flush({bool force = false}) async {
    if (_flushing) return;
    _flushing = true;
    notifyListeners();
    try {
      for (final item in List.of(_items)) {
        if (item.state == PendingUploadState.failed) continue;
        final due = item.nextAttemptAt;
        if (!force && due != null && due.isAfter(_now())) continue;
        final stop = await _send(item);
        if (stop) break;
      }
    } finally {
      _flushing = false;
      await _persist();
      notifyListeners();
    }
  }

  /// Returns true when the run should stop (no connection / session).
  Future<bool> _send(PendingUpload item) async {
    try {
      final result = await _sender(item);
      _items.removeWhere((i) => i.id == item.id);
      await _persist();
      notifyListeners();
      onUploaded?.call(item, result);
      return false;
    } on ApiException catch (e) {
      if (e.isSessionExpired) return true;
      if (e.code == 'proof_already_recorded') {
        // The server already holds this proof: nothing left to send.
        _items.removeWhere((i) => i.id == item.id);
        onUploaded?.call(item, null);
        return false;
      }
      if (isTransientUploadFailure(e)) {
        final attempts = item.attempts + 1;
        _replace(
          item.copyWith(
            attempts: attempts,
            nextAttemptAt: _now().add(backoff(attempts)),
            errorCode: e.code,
          ),
        );
        // Without a connection the rest would fail the same way.
        return e.isOffline;
      }
      _replace(
        item.copyWith(
          state: PendingUploadState.failed,
          errorCode: e.code,
          attempts: item.attempts + 1,
        ),
      );
      return false;
    } on Object {
      // A vanished local file cannot be retried.
      _replace(
        item.copyWith(
          state: PendingUploadState.failed,
          errorCode: 'file_unavailable',
        ),
      );
      return false;
    } finally {
      notifyListeners();
    }
  }

  /// Puts a failed proof back in line (the driver chose to try again).
  Future<void> retry(String id) async {
    final index = _items.indexWhere((i) => i.id == id);
    if (index < 0) return;
    _items[index] = _items[index].copyWith(
      state: PendingUploadState.pending,
      nextAttemptAt: _now(),
      clearError: true,
    );
    await _persist();
    notifyListeners();
    unawaited(flush(force: true));
  }

  Future<void> discard(String id) async {
    _items.removeWhere((i) => i.id == id);
    await _persist();
    notifyListeners();
  }

  /// Drops everything queued for a booking that ended without the proof.
  Future<void> discardBooking(String bookingId) async {
    _items.removeWhere((i) => i.bookingId == bookingId);
    await _persist();
    notifyListeners();
  }
}
