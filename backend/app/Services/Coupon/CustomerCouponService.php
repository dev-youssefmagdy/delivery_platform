<?php

namespace App\Services\Coupon;

use App\DTOs\Coupon\CouponActivityFilterData;
use App\Models\Coupon;
use App\Models\CouponReservation;
use App\Models\User;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * GET /me/coupons: the active automatic promotions the customer can still use (per-customer limit not reached) and their own
 * coupon history. Codes are never listed: a code is a secret the customer was given.
 */
final class CustomerCouponService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly CouponReservationRepositoryInterface $reservations,
    ) {}

    /**
     * @return array{available: Collection<int, Coupon>, history: Collection<int, CouponReservation>}
     */
    public function overview(User $customer): array
    {
        $available = $this->coupons->activeAutomatic(CarbonImmutable::now())->filter(
            fn (Coupon $coupon): bool => $coupon->per_customer_limit === null || $this->reservations->countingForUser($coupon->id, $customer->id) < $coupon->per_customer_limit,
        )->values();

        $history = $this->reservations->reservationsQuery((new CouponActivityFilterData)->forUser($customer->id))->latest('coupon_reservations.id')->limit(20)->get();

        return ['available' => $available, 'history' => $history];
    }
}
