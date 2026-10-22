<?php

namespace App\Services\Courier;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Catalog\BookingType;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Booking\BookingNotifier;
use App\Services\Booking\BookingTransitionService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The chain of custody of a parcel (SRS §5.1-C, §7.5): picked_up, in_transit, arrived_dropoff, delivered, attempt_failed,
 * returning, returned. Each move is checked against `CustodyStatus::allowedNext()`, stored in `bookings.custody_status` and
 * appended to the booking timeline as a status-less event `custody_<value>` (through `BookingTransitionService::annotate`,
 * so timeline order is the booking's own sequence). This service never touches `bookings.status`.
 *
 * Every method must run inside a transaction that already holds the booking row lock.
 */
final class CustodyService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingTransitionService $transitions,
        private readonly BookingNotifier $notifier,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Custody is tracked for parcel bookings (and later for other physical-goods services that opt in).
     */
    public static function applies(Booking $booking): bool
    {
        return $booking->booking_type === BookingType::Parcel;
    }

    /**
     * @param array<string, mixed> $metadata non-PII facts of the event
     *
     * @throws ConflictException `custody_transition_invalid`
     */
    public function move(Booking $booking, CustodyStatus $to, TransitionContext $context, array $metadata = []): void
    {
        $from = $booking->custody_status;

        if ($from === null ? $to !== CustodyStatus::PickedUp : ! $from->canMoveTo($to)) {
            throw ConflictException::make('custody_transition_invalid', context: ['from' => $from?->value, 'to' => $to->value]);
        }

        $this->bookings->forceUpdate($booking, ['custody_status' => $to, 'lock_version' => $booking->lock_version + 1]);
        $this->transitions->annotate($booking, $to->timelineReason(), $context, ['custody_from' => $from?->value] + $metadata);
        $this->notifier->custody($booking->refresh(), $to);
    }

    /**
     * The provider reports a step by hand (`in_transit`, `arrived_dropoff`). A repeat of the step the parcel is already in is
     * answered as a replay.
     *
     * @return array{booking: Booking, replayed: bool}
     *
     * @throws ConflictException `custody_not_active`, `custody_transition_invalid`
     */
    public function report(ProviderProfile $provider, string $bookingUuid, CustodyStatus $event): array
    {
        return $this->db->transaction(function () use ($provider, $bookingUuid, $event): array {
            $booking = $this->bookings->findByUuidForUpdate($bookingUuid);

            if (! $booking instanceof Booking || $booking->provider_id !== $provider->id) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            if (! self::applies($booking) || $booking->status !== BookingStatus::InProgress) {
                throw ConflictException::make('custody_not_active', context: ['booking_status' => $booking->status->value]);
            }

            if ($booking->custody_status === $event) {
                return ['booking' => $booking, 'replayed' => true];
            }

            $this->move($booking, $event, TransitionContext::provider($provider, 'custody'));

            return ['booking' => $booking->refresh(), 'replayed' => false];
        });
    }
}
