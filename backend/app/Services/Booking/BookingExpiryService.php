<?php

namespace App\Services\Booking;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\OfferInvalidationReason;
use App\Enums\Common\ActorType;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;

/**
 * Ends search windows that ran out, without any client involved (SRS §10.2, Plan §2.3). Two triggers share this
 * code: the delayed `ExpireBookingSearch` job at the exact second, and the `bookings:expire-searches` sweep every
 * 5 seconds as backstop. Both re-check the row under its lock, so a window that was extended meanwhile (the request
 * changed) or a booking that already moved on is left alone, and running twice changes nothing.
 */
final class BookingExpiryService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingOfferRepositoryInterface $offers,
        private readonly BookingTransitionService $transitions,
        private readonly BookingNotifier $notifier,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return bool whether the booking was expired by this call
     */
    public function expire(int $bookingId, ?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        $expired = $this->db->transaction(function () use ($bookingId, $now): ?Booking {
            $booking = $this->bookings->lockById($bookingId);

            if (! $booking instanceof Booking || ! $booking->status->isSearching()) {
                return null;
            }

            if ($booking->search_expires_at === null || $booking->search_expires_at->greaterThan($now)) {
                return null;
            }

            $this->offers->invalidateActive($booking, OfferInvalidationReason::SearchExpired);

            try {
                return $this->transitions->transition($booking, BookingStatus::ExpiredNoProvider, new TransitionContext(
                    actorType: ActorType::System,
                    reason: 'search_expired',
                    at: $now,
                ));
            } catch (ConflictException) {
                return null;
            }
        });

        if ($expired instanceof Booking) {
            $this->notifier->searchExpired($expired);
        }

        return $expired instanceof Booking;
    }

    /**
     * Expires every booking whose window ended, oldest first.
     *
     * @return int bookings expired
     */
    public function sweep(?int $limit = null): int
    {
        $now = CarbonImmutable::now();
        $count = 0;

        foreach ($this->bookings->searchesExpiredBefore($now, $limit ?? (int) config('booking.sweep_batch', 200)) as $id) {
            if ($this->expire($id, $now)) {
                $count++;
            }
        }

        return $count;
    }
}
