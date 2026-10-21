<?php

namespace App\Http\Requests\Admin\Finance;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Disable a commission rule: new bookings fall back to the next matching rule or the platform default. Mandatory reason.
 */
class DeactivateCommissionRuleRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'deactivate';
    }

    protected function parameter(): string
    {
        return 'commissionRule';
    }
}
