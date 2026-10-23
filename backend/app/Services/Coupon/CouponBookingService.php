<?php

namespace App\Services\Coupon;

use App\DTOs\Booking\ValidatedBookingRequest;
use App\DTOs\Coupon\CouponPlan;
use App\Enums\Coupon\CouponAttemptContext;
use App\Enums\Coupon\CouponAttemptResult;
use App\Enums\Coupon\CouponReason;
use App\Enums\Identity\ClientApp;
use App\Enums\Notification\NotificationEvent;
use App\Exceptions\CouponRejectedException;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Services\Notification\PartyNotifier;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Coupons at booking creation (ADR-17, SRS §6.4 step 5). Two steps so that a refused coupon is always logged even though the
 * booking transaction rolls back:
 *
 * 1. `prepare` (outside the transaction) - finds the coupon (by the code the customer typed, or the best automatic campaign when
 *    no code was typed), runs the eligibility rules, logs the attempt and returns a plan, or throws CouponRejectedException.
 * 2. `reserve` (inside the booking transaction, after the booking row exists) - locks the coupon, re-checks caps and budget in the
 *    lock, reserves, and points `bookings.coupon_reservation_id` at the reservation.
 *
 * A discount sent by the client is not read anywhere: the request carries a code, the server computes everything.
 */
final class CouponBookingService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly CouponReservationRepositoryInterface $reservations,
        private readonly CouponReservationService $reservationService,
        private readonly CouponEligibilityService $eligibility,
        private readonly CouponDefinitionFactory $definitions,
        private readonly CouponContextBuilder $contexts,
        private readonly CouponAttemptRecorder $attempts,
        private readonly BookingRepositoryInterface $bookings,
        private readonly PartyNotifier $notifier,
    ) {}

    /**
     * @throws CouponRejectedException a typed code that is unknown or not usable for this trip
     */
    public function prepare(User $customer, ?string $code, ValidatedBookingRequest $request): ?CouponPlan
    {
        $code = $code === null ? null : trim($code);
        $context = $this->contexts->forBooking($customer->id, $request);

        if ($code === null || $code === '') {
            return $this->bestAutomatic($customer, $request, $context);
        }

        $coupon = $this->coupons->findByCode($code);

        if (! $coupon instanceof Coupon) {
            $this->attempts->record(CouponAttemptContext::Booking, CouponAttemptResult::Rejected, CouponReason::NotFound, null, $code, $customer->id, $request->category->id);

            throw CouponRejectedException::forReason(CouponReason::NotFound);
        }

        $result = $this->eligibility->evaluate(
            $this->definitions->make($coupon),
            $context->withCustomerUses($this->reservations->countingForUser($coupon->id, $customer->id)),
        );

        if (! $result->isEligible()) {
            $this->attempts->record(CouponAttemptContext::Booking, CouponAttemptResult::Rejected, $result->reason, $coupon->id, $code, $customer->id, $request->category->id);

            throw CouponRejectedException::forReason($result->reason);
        }

        return new CouponPlan($coupon->id, $coupon->code, $result->discount, false);
    }

    /**
     * @throws CouponRejectedException the coupon was used up (or stopped) between the pre-check and the lock
     */
    public function reserve(CouponPlan $plan, Booking $booking, ValidatedBookingRequest $request): Booking
    {
        $reservation = $this->reservationService->reserve($plan, $booking, $this->contexts->forBooking($booking->customer_id, $request));

        $updated = $this->bookings->forceUpdate($booking, ['coupon_reservation_id' => $reservation->id]);
        $this->notifier->customer($updated, NotificationEvent::CouponApplied, (string) $plan->couponId, ['coupon' => $this->couponLabel($plan)]);

        return $updated;
    }

    /**
     * Logs a reservation that lost the race, outside the (rolled back) booking transaction.
     */
    public function recordFailure(CouponPlan $plan, User $customer, CouponRejectedException $exception, ?int $serviceCategoryId): void
    {
        $this->attempts->record(
            $plan->automatic ? CouponAttemptContext::Automatic : CouponAttemptContext::Booking,
            CouponAttemptResult::ReservationFailed,
            $exception->reason(),
            $plan->couponId,
            $plan->codeEntered,
            $customer->id,
            $serviceCategoryId,
        );
        $this->notifier->user($customer, ClientApp::Customer, NotificationEvent::CouponRejected, $plan->couponId.':'.now()->getTimestamp(), ['coupon' => $this->couponLabel($plan)]);
    }

    /**
     * What the customer knows the coupon by: the typed code, else the code or name of the campaign.
     */
    private function couponLabel(CouponPlan $plan): string
    {
        if ($plan->codeEntered !== null && $plan->codeEntered !== '') {
            return $plan->codeEntered;
        }

        $coupon = $this->coupons->find($plan->couponId);

        return $coupon instanceof Coupon ? (string) ($coupon->code ?? $coupon->name) : '';
    }

    /**
     * No code typed: the active automatic campaign with the largest discount for this trip (priority breaks ties). None
     * eligible is not an error. Only the winner is logged; evaluating every campaign would flood the attempt log.
     */
    private function bestAutomatic(User $customer, ValidatedBookingRequest $request, \App\DTOs\Coupon\CouponContext $context): ?CouponPlan
    {
        if (! $request->category->coupon_eligible) {
            return null;
        }

        $best = null;
        $bestDiscount = Money::zero($context->fare->currency);

        foreach ($this->coupons->activeAutomatic(CarbonImmutable::instance($context->at)) as $coupon) {
            $result = $this->eligibility->evaluate(
                $this->definitions->make($coupon),
                $context->withCustomerUses($this->reservations->countingForUser($coupon->id, $customer->id)),
            );

            if ($result->isEligible() && $result->discount->greaterThan($bestDiscount)) {
                $best = $coupon;
                $bestDiscount = $result->discount;
            }
        }

        return $best === null ? null : new CouponPlan($best->id, null, $bestDiscount, true);
    }
}
