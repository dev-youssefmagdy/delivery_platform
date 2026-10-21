<?php

namespace App\Http\Requests\Admin\Pricing;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Enable a fare policy again (it must not overlap another enabled policy of the same scope).
 */
class ActivateFarePolicyRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'activate';
    }

    protected function parameter(): string
    {
        return 'farePolicy';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
