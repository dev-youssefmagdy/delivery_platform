<?php

namespace App\Services\Dispatch;

use App\DTOs\Dispatch\DispatchableRequest;

/**
 * Where dispatch gets the request it is working on. P2.3 binds an implementation that reads the booking; the default
 * binding (`UnavailableDispatchableRequestSource`) finds nothing, so a wave job that runs before then stops quietly.
 */
interface DispatchableRequestSource
{
    public function find(int $bookingId): ?DispatchableRequest;
}
