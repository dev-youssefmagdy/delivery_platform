<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Approve a payout. The note is optional; above the dual-approval threshold a second, different admin must approve as well.
 */
class ApprovePayoutRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'approve';
    }

    protected function parameter(): string
    {
        return 'payoutRequest';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
