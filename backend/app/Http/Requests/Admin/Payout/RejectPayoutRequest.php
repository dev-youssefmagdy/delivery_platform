<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reject a payout request. Mandatory reason; the reservation returns to the provider's wallet.
 */
class RejectPayoutRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reject';
    }

    protected function parameter(): string
    {
        return 'payoutRequest';
    }
}
