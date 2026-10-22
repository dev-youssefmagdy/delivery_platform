<?php

namespace App\Exceptions;

use App\Enums\Coupon\CouponReason;

/**
 * A coupon was refused (422, field `coupon_code`). The stable error code is `coupon_<reason>` and `meta.reason_code` carries the
 * explanation code, so the apps can show the right copy.
 */
class CouponRejectedException extends BusinessRuleViolation
{
    public static function forReason(CouponReason $reason): static
    {
        return static::make('coupon_'.$reason->value, 'coupon_code', ['reason' => $reason->value]);
    }

    public function reason(): CouponReason
    {
        return CouponReason::tryFrom((string) ($this->context['reason'] ?? '')) ?? CouponReason::NotActive;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return parent::meta() + ['reason_code' => $this->reason()->value];
    }
}
