<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Mark an approved payout as being transferred.
 */
class MarkPayoutProcessingRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'markProcessing';
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
