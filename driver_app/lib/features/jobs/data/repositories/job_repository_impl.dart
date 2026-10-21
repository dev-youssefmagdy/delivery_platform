import 'package:platform_core/platform_core.dart';

import '../../domain/entities/job_entities.dart';
import '../../domain/repositories/job_repository.dart';
import '../datasources/job_remote_data_source.dart';
import '../dtos/job_dto.dart';

class JobRepositoryImpl implements JobRepository {
  const JobRepositoryImpl(this._remote);

  final JobRemoteDataSource _remote;

  static MyOffer _requireOffer(Object? data) {
    final offer = JobDto.offer(data);
    if (offer == null) {
      throw const ApiException(
        kind: ApiFailureKind.server,
        code: 'invalid_response',
        message: null,
      );
    }
    return offer;
  }

  @override
  Future<RequestFeed> availableRequests() async =>
      JobDto.feed(await _remote.available());

  @override
  Future<List<MyOffer>> myOffers() async =>
      JobDto.offers((await _remote.offers()).data);

  @override
  Future<MyOffer> acceptCustomerFare(
    String bookingId, {
    required String idempotencyKey,
    int? etaMinutes,
  }) async => _requireOffer(
    (await _remote.acceptCustomerFare(bookingId, {
      'eta_minutes': ?etaMinutes,
    }, idempotencyKey)).data,
  );

  @override
  Future<MyOffer> counterOffer(
    String bookingId, {
    required int amountMinor,
    required String idempotencyKey,
    String? currency,
    int? etaMinutes,
  }) async => _requireOffer(
    (await _remote.counter(bookingId, {
      'amount_minor': amountMinor,
      'currency': ?currency,
      'eta_minutes': ?etaMinutes,
    }, idempotencyKey)).data,
  );

  @override
  Future<void> withdrawOffer(String offerId) async {
    await _remote.withdraw(offerId);
  }

  @override
  Future<void> skip(String bookingId) async {
    await _remote.skip(bookingId);
  }

  @override
  Future<Job?> activeJob() async {
    final data = (await _remote.active()).data;
    return data == null ? null : JobDto.job(data);
  }

  @override
  Future<Job> job(String bookingId) async =>
      JobDto.job((await _remote.job(bookingId)).data);

  Future<Job> _step(
    String id,
    String step,
    String key, {
    Map<String, Object?>? body,
  }) async =>
      JobDto.job((await _remote.transition(id, step, key, body: body)).data);

  @override
  Future<Job> acknowledge(String bookingId, {required String idempotencyKey}) =>
      _step(bookingId, 'acknowledge', idempotencyKey);

  @override
  Future<Job> arrived(
    String bookingId, {
    required String idempotencyKey,
    Map<String, Object?> evidence = const {},
  }) => _step(bookingId, 'arrived', idempotencyKey, body: evidence);

  @override
  Future<Job> verifyPin(
    String bookingId,
    String pin, {
    required String idempotencyKey,
  }) => _step(
    bookingId,
    'verify-start-pin',
    idempotencyKey,
    body: {'pin': pin},
  );

  @override
  Future<Job> start(String bookingId, {required String idempotencyKey}) =>
      _step(bookingId, 'start', idempotencyKey);

  @override
  Future<Job> complete(String bookingId, {required String idempotencyKey}) =>
      _step(bookingId, 'complete', idempotencyKey);

  @override
  Future<CancellationPreview> previewCancel(
    String bookingId,
    DriverCancelReason reason, {
    String? note,
  }) async {
    final result = await _remote.cancel(bookingId, {
      'reason_code': reason.value,
      if (note != null && note.isNotEmpty) 'note': note,
      'preview': true,
    });
    return JobDto.cancellation(result.meta['cancellation']);
  }

  @override
  Future<CancelOutcome> cancel(
    String bookingId,
    DriverCancelReason reason, {
    required String idempotencyKey,
    String? note,
  }) async {
    final result = await _remote.cancel(bookingId, {
      'reason_code': reason.value,
      if (note != null && note.isNotEmpty) 'note': note,
    }, key: idempotencyKey);
    return CancelOutcome(
      committed: result.meta['committed'] != false,
      redispatched: result.meta['redispatched'] == true,
      decision: JobDto.cancellation(result.meta['cancellation']),
      job: result.data == null ? null : JobDto.job(result.data),
    );
  }

  @override
  Future<void> rateCustomer(
    String bookingId, {
    required int stars,
    required List<CustomerRatingTag> tags,
    String? comment,
    required String idempotencyKey,
  }) async {
    await _remote.rating(bookingId, {
      'stars': stars,
      'tags': [for (final t in tags) t.value],
      if (comment != null && comment.isNotEmpty) 'comment': comment,
    }, idempotencyKey);
  }

  @override
  Future<void> report(
    String bookingId, {
    required DriverReportCategory category,
    required String description,
    required String idempotencyKey,
  }) async {
    await _remote.report(bookingId, {
      'category': category.value,
      'description': description,
    }, idempotencyKey);
  }
}
