<?php

namespace App\Services\Dispatch;

use App\DTOs\Dispatch\DispatchableRequest;

/**
 * Placeholder until bookings exist (P2.3): there is nothing to dispatch.
 */
final class UnavailableDispatchableRequestSource implements DispatchableRequestSource
{
    public function find(int $bookingId): ?DispatchableRequest
    {
        return null;
    }
}
