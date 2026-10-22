<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Archive a coupon for good: it can no longer be used or edited and keeps its history. Mandatory reason (audited).
 */
class ArchiveCouponRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'archive';
    }

    protected function parameter(): string
    {
        return 'coupon';
    }
}
