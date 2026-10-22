<?php

namespace App\Http\Requests\Admin\Finance;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reject an adjustment request. Mandatory reason.
 */
class RejectAdjustmentRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reject';
    }

    protected function parameter(): string
    {
        return 'adjustmentRequest';
    }

}
