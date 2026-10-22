<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Pause a coupon: customers cannot use it until it is resumed. Mandatory reason (audited).
 */
class PauseCouponRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'pause';
    }

    protected function parameter(): string
    {
        return 'coupon';
    }
}
