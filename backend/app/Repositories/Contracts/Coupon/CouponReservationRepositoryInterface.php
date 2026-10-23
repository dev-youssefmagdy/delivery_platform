<?php

namespace App\Repositories\Contracts\Coupon;

use App\DTOs\Coupon\CouponActivityFilterData;
use App\Enums\Finance\DiscountFunding;
use App\Models\CouponRedemption;
use App\Models\CouponReservation;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reservations, redemptions and funding entries of coupons. Reservations are never deleted: their status says what became of them.
 *
 * @extends BaseRepositoryInterface<CouponReservation>
 */
interface CouponReservationRepositoryInterface extends BaseRepositoryInterface
{
    public function forBooking(int $bookingId): ?CouponReservation;

    public function lockForBooking(int $bookingId): ?CouponReservation;

    /**
     * Reservations of the customer for this coupon that count against the per-customer limit (reserved or committed).
     */
    public function countingForUser(int $couponId, int $userId): int;

    /**
     * Reservations still `reserved` whose booking ended without a trip (cancelled / expired): the safety net for a missed release.
     *
     * @return Collection<int, CouponReservation>
     */
    public function orphaned(): Collection;

    public function redemptionFor(int $reservationId): ?CouponRedemption;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createRedemption(array $attributes): CouponRedemption;

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateRedemption(CouponRedemption $redemption, array $attributes): CouponRedemption;

    public function addFunding(int $couponId, int $redemptionId, int $bookingId, DiscountFunding $funder, string $kind, int $amountMinor, string $currency): void;

    /**
     * Links the accrued funding entries of a booking to the ledger entry that settled the trip (idempotent: only unlinked rows).
     */
    public function linkFundingToEntry(int $bookingId, int $journalEntryId): void;

    /**
     * @return Builder<CouponRedemption>
     */
    public function redemptionsQuery(CouponActivityFilterData $filters): Builder;

    /**
     * Reservations (with their redemption) for the customer's coupon tab.
     *
     * @return Builder<CouponReservation>
     */
    public function reservationsQuery(CouponActivityFilterData $filters): Builder;

    /**
     * Aggregates for the performance tab.
     *
     * @return array{redemptions: int, reversed: int, discount_minor: int, platform_minor: int, provider_minor: int, partner_minor: int, customers: int}
     */
    public function performance(int $couponId): array;

    /**
     * @return array<string, array{count: int, discount_minor: int}> day (Y-m-d) => totals of committed redemptions, newest `$days` days
     */
    public function dailyRedemptions(int $couponId, int $days): array;
}
