<?php

namespace App\Http\Requests\Admin\Finance;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Enable a commission rule again.
 */
class ActivateCommissionRuleRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'activate';
    }

    protected function parameter(): string
    {
        return 'commissionRule';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
