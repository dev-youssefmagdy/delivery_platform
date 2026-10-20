<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reject a vehicle under review: mandatory reason shown to the provider.
 */
class RejectVehicleRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reject';
    }

    protected function parameter(): string
    {
        return 'vehicle';
    }
}
