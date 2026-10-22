<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Resume a paused coupon (back to active, or scheduled when its start is ahead).
 */
class ResumeCouponRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'resume';
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
