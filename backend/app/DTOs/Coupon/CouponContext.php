<?php

namespace App\DTOs\Coupon;

use App\Enums\Payments\PaymentMethodType;
use App\Support\Money\Money;
use Carbon\CarbonInterface;

/**
 * What a coupon is evaluated against: the customer, the service and place, the payment method, the time and the fare.
 * Built by the server from its own data (never from a client discount); the fare is the agreed fare at completion, the
 * proposed fare (or its upper bound) when a booking is created.
 */
final readonly class CouponContext
{
    /**
     * @param list<int> $vehicleCategoryIds the vehicle categories the booking can be served with
     */
    public function __construct(
        public ?int $userId,
        public bool $isNewCustomer,
        public int $customerUses,
        public ?int $serviceFamilyId,
        public ?int $serviceCategoryId,
        public bool $categoryAllowsCoupons,
        public array $vehicleCategoryIds,
        public ?int $cityId,
        public ?int $zoneId,
        public ?PaymentMethodType $paymentMethod,
        public Money $fare,
        public CarbonInterface $at,
        public bool $hasOtherDiscount = false,
    ) {}

    public function withCustomerUses(int $uses): self
    {
        return new self(
            $this->userId, $this->isNewCustomer, $uses, $this->serviceFamilyId, $this->serviceCategoryId, $this->categoryAllowsCoupons,
            $this->vehicleCategoryIds, $this->cityId, $this->zoneId, $this->paymentMethod, $this->fare, $this->at, $this->hasOtherDiscount,
        );
    }
}
