<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * A paid transfer came back. Mandatory reason; the paid entry and the reservation are reversed in the ledger.
 */
class ReversePayoutRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reverse';
    }

    protected function parameter(): string
    {
        return 'payoutRequest';
    }
}
