<?php

namespace App\Http\Requests\Admin\Booking;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Destroy a cancellation policy (reason mandatory: true).
 */
class DestroyCancellationPolicyRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'delete';
    }

    protected function parameter(): string
    {
        return 'cancellationPolicy';
    }
}
