import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../core/providers.dart';
import '../../data/dtos/booking_dto.dart';
import '../../domain/entities/booking_entities.dart';
import 'booking_providers.dart';

final deliveryRepositoryProvider = Provider<DeliveryRepository>(
  (ref) => DeliveryRepository(
    ref.watch(apiClientProvider),
    bookingPrefix: '/bookings',
  ),
);

final bookingAttachmentRepositoryProvider =
    Provider<BookingAttachmentRepository>(
      (ref) => BookingAttachmentRepository(ref.watch(apiClientProvider)),
    );

/// Proofs of the customer's job (pictures behind signed URLs).
final bookingProofsProvider = FutureProvider.autoDispose
    .family<List<BookingProof>, String>(
      (ref, id) => ref.watch(deliveryRepositoryProvider).proofs(id),
      retry: (_, _) => null,
    );

/// The customer's own load files (photos / videos / documents).
final bookingAttachmentsProvider = FutureProvider.autoDispose
    .family<List<AttachmentInfo>, String>(
      (ref, id) => ref.watch(deliveryRepositoryProvider).attachments(id),
      retry: (_, _) => null,
    );

/// Retry / return / operations after a failed attempt. One Idempotency-Key
/// per (attempt, decision), kept across retries until the call succeeded or
/// was refused for good.
class CustomerDeliveryActions {
  CustomerDeliveryActions(this._ref);

  final Ref _ref;
  final _keys = <String, String>{};

  Future<Booking> resolveAttempt(
    String bookingId,
    String attemptId,
    AttemptResolution resolution,
  ) async {
    final intent = 'resolve:$bookingId:$attemptId:${resolution.value}';
    final key = _keys.putIfAbsent(intent, newIdempotencyKey);
    try {
      final data = await _ref
          .read(deliveryRepositoryProvider)
          .resolveAttempt(bookingId, attemptId, resolution, key: key);
      _keys.remove(intent);
      final booking = BookingDto.booking(data);
      _ref.read(bookingTrackerProvider(bookingId).notifier).apply(booking);
      _ref.invalidate(activeBookingProvider);
      return booking;
    } on ApiException catch (e) {
      final transient =
          e.isOffline ||
          e.kind == ApiFailureKind.server ||
          e.kind == ApiFailureKind.rateLimited;
      if (!transient) {
        _keys.remove(intent);
        if (e.kind == ApiFailureKind.conflict ||
            e.kind == ApiFailureKind.validation) {
          _ref.read(bookingTrackerProvider(bookingId).notifier).refresh();
        }
      }
      rethrow;
    }
  }
}

final customerDeliveryActionsProvider = Provider<CustomerDeliveryActions>(
  CustomerDeliveryActions.new,
);
