<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Start the review of a payout request (the payout is then under review). No reason needed.
 */
class StartPayoutReviewRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'review';
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
