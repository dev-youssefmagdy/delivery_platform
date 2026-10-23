<?php

namespace App\Http\Requests\Admin\Finance;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * The requester withdraws their own pending adjustment. Mandatory reason.
 */
class CancelAdjustmentRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'cancel';
    }

    protected function parameter(): string
    {
        return 'adjustmentRequest';
    }
}
