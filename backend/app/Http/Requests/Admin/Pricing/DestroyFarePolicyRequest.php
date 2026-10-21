<?php

namespace App\Http\Requests\Admin\Pricing;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Delete a fare policy that never priced a booking: mandatory reason.
 */
class DestroyFarePolicyRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'delete';
    }

    protected function parameter(): string
    {
        return 'farePolicy';
    }
}
