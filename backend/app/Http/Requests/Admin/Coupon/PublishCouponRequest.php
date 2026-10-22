<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Publish a draft coupon: scheduled when its start is ahead, active otherwise. No reason is needed.
 */
class PublishCouponRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'publish';
    }

    protected function parameter(): string
    {
        return 'coupon';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
