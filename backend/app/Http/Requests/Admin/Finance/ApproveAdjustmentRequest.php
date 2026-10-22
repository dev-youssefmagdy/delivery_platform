<?php

namespace App\Http\Requests\Admin\Finance;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Approve an adjustment request. The note is optional; the policy refuses the admin who asked.
 */
class ApproveAdjustmentRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'approve';
    }

    protected function parameter(): string
    {
        return 'adjustmentRequest';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
