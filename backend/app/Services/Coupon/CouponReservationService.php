<?php

namespace App\Services\Coupon;

use App\DTOs\Coupon\CouponContext;
use App\DTOs\Coupon\CouponDiscount;
use App\DTOs\Coupon\CouponPlan;
use App\DTOs\Finance\PriceBreakdown;
use App\Enums\Coupon\CouponAttemptContext;
use App\Enums\Coupon\CouponAttemptResult;
use App\Enums\Coupon\CouponRedemptionStatus;
use App\Enums\Coupon\CouponReservationStatus;
use App\Enums\Finance\DiscountFunding;
use App\Exceptions\CouponRejectedException;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponReservation;
use App\Models\CouponVersion;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use LogicException;

/**
 * The only writer of a coupon's counters and of reservations / redemptions (ADR-17, SRS §11.2):
 *
 * - `reserve` (booking creation): LOCKS the coupon row, re-reads the counters and the customer's own count inside the lock and
 *   re-runs the eligibility rules, so concurrent requests can never pass the global limit, the per-customer limit or the budget.
 *   The reservation holds the largest discount it can grant (`hold_minor`) from the budget.
 * - `release` (cancellation / expiry): gives the use and the held money back.
 * - `commit` (completion): the use and the real discount become spent; a redemption and the funding entries are written.
 * - `reverse` (refund): when the coupon's version says `restore_on_refund`, the redemption is reversed and the use and money
 *   return to the coupon; otherwise the discount is retained.
 *
 * `reserve` takes the coupon lock first; `release`, `commit` and `reverse` take the reservation (found by its own booking) and then
 * the coupon. `reserve` never locks an existing reservation, so the paths cannot deadlock each other.
 */
final class CouponReservationService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly CouponReservationRepositoryInterface $reservations,
        private readonly CouponEligibilityService $eligibility,
        private readonly CouponDefinitionFactory $definitions,
        private readonly CouponAttemptRecorder $attempts,
        private readonly CurrencyRegistry $currencies,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws CouponRejectedException the coupon stopped being usable between the pre-check and the lock
     */
    public function reserve(CouponPlan $plan, Booking $booking, CouponContext $context): CouponReservation
    {
        return $this->db->transaction(function () use ($plan, $booking, $context): CouponReservation {
            $coupon = $this->lockCoupon($plan->couponId);
            $version = $this->coupons->currentVersion($coupon) ?? throw new LogicException('A coupon always has a current version.');
            $uses = $context->userId === null ? 0 : $this->reservations->countingForUser($coupon->id, $context->userId);
            $result = $this->eligibility->evaluate($this->definitions->make($coupon, $version->termsObject()), $context->withCustomerUses($uses));

            if (! $result->isEligible()) {
                throw CouponRejectedException::forReason($result->reason);
            }

            $hold = $result->discount;
            $reservation = $this->reservations->create([
                'uuid' => (string) Str::uuid(),
                'coupon_id' => $coupon->id,
                'coupon_version_id' => $version->id,
                'user_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'status' => CouponReservationStatus::Reserved,
                'hold_minor' => $hold->amount,
                'currency' => $hold->currency->code,
                'reserved_at' => CarbonImmutable::now(),
            ]);
            $this->coupons->adjustCounters($coupon, reservedCount: 1, reservedMinor: $hold->amount);

            $this->attempts->record(
                $plan->automatic ? CouponAttemptContext::Automatic : CouponAttemptContext::Booking,
                CouponAttemptResult::Reserved,
                $result->reason,
                $coupon->id,
                $plan->codeEntered,
                $booking->customer_id,
                $booking->service_category_id,
                $booking->id,
                $hold->amount,
            );

            return $reservation;
        });
    }

    public function release(Booking $booking, string $reason): bool
    {
        return $this->db->transaction(function () use ($booking, $reason): bool {
            $reservation = $this->reservations->lockForBooking($booking->id);

            if (! $reservation instanceof CouponReservation || $reservation->status !== CouponReservationStatus::Reserved) {
                return false;
            }

            $coupon = $this->lockCoupon($reservation->coupon_id);
            $this->coupons->adjustCounters($coupon, reservedCount: -1, reservedMinor: -$reservation->hold_minor);
            $this->reservations->update($reservation, [
                'status' => CouponReservationStatus::Released,
                'released_reason' => mb_substr($reason, 0, 64),
                'released_at' => CarbonImmutable::now(),
            ]);

            return true;
        });
    }

    /**
     * Idempotent: a committed (or reversed) reservation is left alone. A reservation whose discount turned out to be zero is released.
     */
    public function commit(Booking $booking, PriceBreakdown $breakdown): bool
    {
        return $this->db->transaction(function () use ($booking, $breakdown): bool {
            $reservation = $this->reservations->lockForBooking($booking->id);

            if (! $reservation instanceof CouponReservation || $reservation->status !== CouponReservationStatus::Reserved) {
                return false;
            }

            if (! $breakdown->discount->isPositive()) {
                $this->release($booking, 'no_discount');

                return false;
            }

            $now = CarbonImmutable::now();
            $coupon = $this->lockCoupon($reservation->coupon_id);
            $this->coupons->adjustCounters($coupon, usedCount: 1, reservedCount: -1, spentMinor: $breakdown->discount->amount, reservedMinor: -$reservation->hold_minor);
            $this->reservations->update($reservation, ['status' => CouponReservationStatus::Committed, 'committed_at' => $now]);

            $redemption = $this->reservations->createRedemption([
                'uuid' => (string) Str::uuid(),
                'coupon_id' => $coupon->id,
                'coupon_version_id' => $reservation->coupon_version_id,
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'booking_id' => $booking->id,
                'status' => CouponRedemptionStatus::Committed,
                'currency' => $reservation->currency,
                'fare_minor' => $breakdown->providerGross->amount,
                'discount_minor' => $breakdown->discount->amount,
                'platform_minor' => $breakdown->discountPlatform->amount,
                'provider_minor' => $breakdown->discountProvider->amount,
                'partner_minor' => $breakdown->discountPartner->amount,
                'redeemed_at' => $now,
            ]);

            foreach ([[DiscountFunding::Platform, $breakdown->discountPlatform], [DiscountFunding::Provider, $breakdown->discountProvider], [DiscountFunding::Partner, $breakdown->discountPartner]] as [$funder, $amount]) {
                $this->reservations->addFunding($coupon->id, $redemption->id, $booking->id, $funder, 'accrued', $amount->amount, $reservation->currency);
            }

            return true;
        });
    }

    /**
     * A refund of the whole trip payment: reverses the redemption when the coupon's version restores on refund.
     *
     * @return bool true when the coupon use and money were given back
     */
    public function reverse(Booking $booking, string $reason): bool
    {
        return $this->db->transaction(function () use ($booking, $reason): bool {
            $reservation = $this->reservations->lockForBooking($booking->id);

            if (! $reservation instanceof CouponReservation || $reservation->status !== CouponReservationStatus::Committed) {
                return false;
            }

            $version = $this->coupons->version($reservation->coupon_version_id);
            $redemption = $this->reservations->redemptionFor($reservation->id);

            if (! $version instanceof CouponVersion || $redemption === null || ! $version->termsObject()->restoreOnRefund) {
                return false;
            }

            $now = CarbonImmutable::now();
            $coupon = $this->lockCoupon($reservation->coupon_id);
            $this->coupons->adjustCounters($coupon, usedCount: -1, spentMinor: -$redemption->discount_minor);
            $this->reservations->update($reservation, ['status' => CouponReservationStatus::Reversed, 'reversed_at' => $now]);
            $this->reservations->updateRedemption($redemption, ['status' => CouponRedemptionStatus::Reversed, 'reversed_at' => $now, 'reversal_reason' => mb_substr($reason, 0, 64)]);

            foreach ([[DiscountFunding::Platform, $redemption->platform_minor], [DiscountFunding::Provider, $redemption->provider_minor], [DiscountFunding::Partner, $redemption->partner_minor]] as [$funder, $amount]) {
                $this->reservations->addFunding($coupon->id, $redemption->id, $booking->id, $funder, 'reversed', $amount, $redemption->currency);
            }

            return true;
        });
    }

    /**
     * The discount a booking's reservation grants on a fare, under the terms of the version it was made with (not the coupon's
     * current ones). Zero below the version's minimum fare; never above what the reservation holds. A committed redemption
     * returns its recorded amount, so a later price calculation cannot drift.
     */
    public function discountFor(Booking $booking, Money $fare): ?CouponDiscount
    {
        if ($booking->coupon_reservation_id === null) {
            return null;
        }

        $reservation = $this->reservations->forBooking($booking->id);

        if (! $reservation instanceof CouponReservation) {
            return null;
        }

        $terms = $reservation->version->termsObject();
        $currency = $this->currencies->get($reservation->currency);

        if ($reservation->status === CouponReservationStatus::Committed || $reservation->status === CouponReservationStatus::Reversed) {
            $redemption = $this->reservations->redemptionFor($reservation->id);

            return $redemption === null ? null : new CouponDiscount(Money::of($redemption->discount_minor, $currency), $terms->funding(), $reservation->id);
        }

        if ($reservation->status !== CouponReservationStatus::Reserved) {
            return null;
        }

        $discount = $terms->discountFor($fare)->min(Money::of($reservation->hold_minor, $currency));

        return new CouponDiscount($discount, $terms->funding(), $reservation->id);
    }

    public function linkSettlement(Booking $booking, int $journalEntryId): void
    {
        $this->reservations->linkFundingToEntry($booking->id, $journalEntryId);
    }

    private function lockCoupon(int $id): Coupon
    {
        $coupon = $this->coupons->lockById($id);

        if (! $coupon instanceof Coupon) {
            throw new LogicException("Coupon [{$id}] vanished.");
        }

        return $coupon->loadMissing('scopes');
    }
}
