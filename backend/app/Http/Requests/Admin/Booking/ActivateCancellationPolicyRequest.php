<?php

namespace App\Http\Requests\Admin\Booking;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Activate a cancellation policy (reason mandatory: false).
 */
class ActivateCancellationPolicyRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'activate';
    }

    protected function parameter(): string
    {
        return 'cancellationPolicy';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
