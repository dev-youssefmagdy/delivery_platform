<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Withdraw the verification of a payout method. Mandatory reason.
 */
class UnverifyPayoutMethodRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'unverify';
    }

    protected function parameter(): string
    {
        return 'payoutMethod';
    }

}
