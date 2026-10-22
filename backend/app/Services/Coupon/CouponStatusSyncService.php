<?php

namespace App\Services\Coupon;

use App\Enums\Coupon\CouponStatus;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponReservation;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * The scheduled status transitions of coupons (`coupons:sync-status`, every minute; idempotent):
 * scheduled -> active at the start, any open status -> expired at the end, active -> exhausted when the committed uses or
 * spend reach the global limit / budget, exhausted -> active when capacity came back (a refund reversal, a raised cap). It also
 * releases reservations whose booking ended without a trip, the safety net for a missed release (ADR-17).
 */
final class CouponStatusSyncService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly CouponReservationRepositoryInterface $reservations,
        private readonly CouponLifecycleService $lifecycle,
        private readonly CouponReservationService $reservationService,
    ) {}

    /**
     * @return array{activated: int, expired: int, exhausted: int, reopened: int, released: int}
     */
    public function sync(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $counts = ['activated' => 0, 'expired' => 0, 'exhausted' => 0, 'reopened' => 0, 'released' => 0];

        $counts['expired'] = $this->apply($this->coupons->dueToExpire($now), CouponStatus::Expired, 'period_ended');
        $counts['activated'] = $this->apply($this->coupons->dueToStart($now), CouponStatus::Active, 'period_started');
        $counts['exhausted'] = $this->apply($this->coupons->consumed(), CouponStatus::Exhausted, 'limit_reached');
        $counts['reopened'] = $this->apply($this->coupons->withCapacityAgain(), CouponStatus::Active, 'capacity_returned', $now);

        foreach ($this->reservations->orphaned() as $reservation) {
            /** @var CouponReservation $reservation */
            $booking = $reservation->booking;

            if ($booking instanceof Booking && $this->reservationService->release($booking, 'booking_ended')) {
                $counts['released']++;
            }
        }

        return $counts;
    }

    /**
     * @param iterable<Coupon> $coupons
     */
    private function apply(iterable $coupons, CouponStatus $to, string $reason, ?CarbonImmutable $reopenAt = null): int
    {
        $changed = 0;

        foreach ($coupons as $coupon) {
            // A coupon that ran out of capacity does not reopen after its period ended.
            if ($reopenAt !== null && $coupon->ends_at !== null && $coupon->ends_at->lessThanOrEqualTo($reopenAt)) {
                continue;
            }

            try {
                $this->lifecycle->system($coupon, $to, $reason);
                $changed++;
            } catch (ConflictException) {
                // Another worker moved it first, or the graph forbids it: nothing to do.
            }
        }

        return $changed;
    }
}
