<?php

namespace App\Services\Dispatch;

use App\DTOs\Dispatch\DispatchableRequest;

/**
 * Where dispatch gets the request it is working on. Bound to `DispatchableBookingSource`, which builds it from the
 * booking row; an unknown booking finds nothing, so a wave job for a vanished booking stops quietly.
 */
interface DispatchableRequestSource
{
    public function find(int $bookingId): ?DispatchableRequest;
}
