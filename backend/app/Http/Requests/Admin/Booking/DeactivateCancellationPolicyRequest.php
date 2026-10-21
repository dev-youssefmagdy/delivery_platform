<?php

namespace App\Http\Requests\Admin\Booking;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Deactivate a cancellation policy (reason mandatory: true).
 */
class DeactivateCancellationPolicyRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'deactivate';
    }

    protected function parameter(): string
    {
        return 'cancellationPolicy';
    }
}
