<?php

namespace App\Http\Requests\Admin\Payments;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reject a refund that waits for approval. Mandatory reason.
 */
class RejectRefundRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reject';
    }

    protected function parameter(): string
    {
        return 'paymentRefund';
    }
}
