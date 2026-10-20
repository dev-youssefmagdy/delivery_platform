<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Approve a vehicle under review.
 */
class ApproveVehicleRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'approve';
    }

    protected function parameter(): string
    {
        return 'vehicle';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
