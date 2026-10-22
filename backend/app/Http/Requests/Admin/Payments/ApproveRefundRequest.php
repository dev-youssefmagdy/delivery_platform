<?php

namespace App\Http\Requests\Admin\Payments;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Approve a refund that waits for a second admin. The note is optional; the policy refuses the requester.
 */
class ApproveRefundRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'approve';
    }

    protected function parameter(): string
    {
        return 'paymentRefund';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
