import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/providers.dart';
import '../../data/dtos/job_dto.dart';
import '../../domain/entities/job_entities.dart';
import '../../../availability/presentation/providers/availability_providers.dart';
import 'job_providers.dart';

final deliveryRepositoryProvider = Provider<DeliveryRepository>(
  (ref) => DeliveryRepository(
    ref.watch(apiClientProvider),
    bookingPrefix: '/provider/bookings',
  ),
);

/// Proofs the server already holds for a job (viewer).
final jobProofsProvider = FutureProvider.autoDispose.family<List<BookingProof>, String>(
  (ref, id) => ref.watch(deliveryRepositoryProvider).proofs(id),
  retry: (_, _) => null,
);

/// Load files of the job (photos, videos, documents) behind signed URLs.
final jobAttachmentsProvider = FutureProvider.autoDispose.family<List<AttachmentInfo>, String>(
  (ref, id) => ref.watch(deliveryRepositoryProvider).attachments(id),
  retry: (_, _) => null,
);

/// The proof queue keeps its items in SharedPreferences so a captured proof
/// survives a restart and a dead connection.
class _PrefsUploadStore implements UploadQueueStore {
  const _PrefsUploadStore(this._prefs);

  static const _key = 'proof_upload_queue';

  final SharedPreferences _prefs;

  @override
  List<String> read() => _prefs.getStringList(_key) ?? const [];

  @override
  Future<void> write(List<String> items) => _prefs.setStringList(_key, items);
}

/// Offline-safe proof uploads (pickup proof, delivery proofs). Retries when
/// the connection comes back and on a slow timer; the booking is refreshed
/// after each stored proof.
class ProofQueueNotifier extends Notifier<List<PendingUpload>> {
  late ProofUploadQueue _queue;

  @override
  List<PendingUpload> build() {
    final repo = ref.watch(deliveryRepositoryProvider);
    _queue = ProofUploadQueue(
      store: _PrefsUploadStore(ref.watch(sharedPreferencesProvider)),
      sender: (item) => repo.uploadProof(
        item.bookingId,
        item.toInput(),
        key: item.idempotencyKey,
      ),
      onUploaded: (item, result) {
        final data = result?.booking;
        if (data != null) {
          try {
            ref.read(jobTrackerProvider(item.bookingId).notifier).apply(JobDto.job(data));
          } on Object {
            unawaited(ref.read(jobTrackerProvider(item.bookingId).notifier).refresh());
          }
        } else {
          unawaited(ref.read(jobTrackerProvider(item.bookingId).notifier).refresh());
        }
        ref.invalidate(jobProofsProvider(item.bookingId));
      },
    );
    void sync() => state = _queue.items;
    _queue.addListener(sync);
    ref.listen<AsyncValue<bool>>(onlineProvider, (previous, next) {
      if (next.value == true) unawaited(_queue.flush(force: true));
    });
    final timer = Timer.periodic(const Duration(seconds: 20), (_) => unawaited(_queue.flush()));
    ref.onDispose(() {
      timer.cancel();
      _queue.removeListener(sync);
      _queue.dispose();
    });
    // First run: send what an earlier session left behind.
    scheduleMicrotask(() => unawaited(_queue.flush()));
    return _queue.items;
  }

  Future<PendingUpload> capture(String bookingId, ProofInput input) =>
      _queue.enqueue(bookingId: bookingId, input: input, newKey: newIdempotencyKey);

  Future<void> retry(String id) => _queue.retry(id);

  Future<void> discard(String id) => _queue.discard(id);

  Future<void> flush() => _queue.flush(force: true);
}

final proofQueueProvider =
    NotifierProvider<ProofQueueNotifier, List<PendingUpload>>(ProofQueueNotifier.new);

/// Pending proofs of one job.
final jobPendingProofsProvider = Provider.autoDispose.family<List<PendingUpload>, String>(
  (ref, id) => [
    for (final p in ref.watch(proofQueueProvider))
      if (p.bookingId == id) p,
  ],
);

/// Field work on a parcel / moving / freight job. Every call keeps one
/// Idempotency-Key per user intent until it succeeded or was refused for good
/// (the same rule as [JobActions]); the returned booking replaces the one on
/// screen.
class DeliveryActions {
  DeliveryActions(this._ref);

  final Ref _ref;
  final _keys = <String, String>{};
  final _pending = <String, Future<Object?>>{};

  DeliveryRepository get _repo => _ref.read(deliveryRepositoryProvider);

  Future<T> _run<T>(String intent, Future<T> Function(String key) call) {
    final inFlight = _pending[intent];
    if (inFlight != null) return inFlight.then((v) => v as T);
    final future = () async {
      final key = _keys.putIfAbsent(intent, newIdempotencyKey);
      try {
        final result = await call(key);
        _keys.remove(intent);
        return result;
      } on ApiException catch (e) {
        if (!keepsKey(e)) _keys.remove(intent);
        rethrow;
      }
    }();
    _pending[intent] = future;
    return future.whenComplete(() => _pending.remove(intent));
  }

  Job _apply(String bookingId, Object? data) {
    final job = JobDto.job(data);
    _ref.read(jobTrackerProvider(bookingId).notifier).apply(job);
    unawaited(_ref.read(availabilityProvider.notifier).refresh());
    return job;
  }

  /// A server refusal that changed the booking (code locked, wrong step)
  /// shows the fresh state.
  Future<R> _refreshOnRefusal<R>(String bookingId, Future<R> Function() call) async {
    try {
      return await call();
    } on ApiException catch (e) {
      if (e.kind == ApiFailureKind.conflict || e.kind == ApiFailureKind.validation) {
        unawaited(_ref.read(jobTrackerProvider(bookingId).notifier).refresh());
      }
      rethrow;
    }
  }

  Future<Job> custody(String bookingId, CustodyStatus event) => _refreshOnRefusal(
    bookingId,
    () => _run(
      'custody:$bookingId:${event.value}',
      (key) async => _apply(bookingId, await _repo.custody(bookingId, event, key: key)),
    ),
  );

  /// Enters the code the recipient read out. A wrong code is a new intent per
  /// code, so the same wrong code is not replayed by mistake.
  Future<Job> verifyOtp(String bookingId, String code) => _refreshOnRefusal(
    bookingId,
    () => _run(
      'otp:$bookingId:$code',
      (key) async => _apply(bookingId, await _repo.verifyRecipientOtp(bookingId, code, key: key)),
    ),
  );

  Future<Job> resendOtp(String bookingId) => _refreshOnRefusal(
    bookingId,
    () => _run(
      'otp-resend:$bookingId:${DateTime.now().millisecondsSinceEpoch ~/ 60000}',
      (key) async => _apply(bookingId, await _repo.resendRecipientOtp(bookingId, key: key)),
    ),
  );

  /// Reports a failed attempt. The evidence picture is part of the intent: the
  /// same outcome, reason and file keep the same key across retries.
  Future<Job> reportAttempt(
    String bookingId, {
    required AttemptOutcome outcome,
    String? reason,
    String? evidencePath,
  }) => _refreshOnRefusal(
    bookingId,
    () => _run(
      'attempt:$bookingId:${outcome.value}:${reason ?? ''}:${evidencePath ?? ''}',
      (key) async {
        final result = await _repo.reportAttempt(
          bookingId,
          outcome: outcome,
          reason: reason,
          evidencePath: evidencePath,
          key: key,
        );
        return _apply(bookingId, result.booking);
      },
    ),
  );

  Future<Job> resolveAttempt(String bookingId, String attemptId, AttemptResolution resolution) =>
      _refreshOnRefusal(
        bookingId,
        () => _run(
          'resolve:$bookingId:$attemptId:${resolution.value}',
          (key) async => _apply(
            bookingId,
            await _repo.resolveAttempt(bookingId, attemptId, resolution, key: key),
          ),
        ),
      );
}

final deliveryActionsProvider = Provider<DeliveryActions>(DeliveryActions.new);
