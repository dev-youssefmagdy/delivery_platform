<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * The transfer did not happen. Mandatory reason; the reservation returns to the provider's wallet.
 */
class MarkPayoutFailedRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'markFailed';
    }

    protected function parameter(): string
    {
        return 'payoutRequest';
    }

}
