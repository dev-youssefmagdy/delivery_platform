<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Verify a provider's payout method. The note records how the destination was checked.
 */
class VerifyPayoutMethodRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'verify';
    }

    protected function parameter(): string
    {
        return 'payoutMethod';
    }
}
