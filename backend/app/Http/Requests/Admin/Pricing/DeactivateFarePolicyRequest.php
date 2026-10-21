<?php

namespace App\Http\Requests\Admin\Pricing;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Disable a fare policy: new bookings no longer get guidance from it. Mandatory reason.
 */
class DeactivateFarePolicyRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'deactivate';
    }

    protected function parameter(): string
    {
        return 'farePolicy';
    }
}
