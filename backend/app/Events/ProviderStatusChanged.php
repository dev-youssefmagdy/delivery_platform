<?php

namespace App\Events;

use App\Enums\Provider\ProviderStatus;
use App\Models\ProviderProfile;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A provider moved to another status (dispatched after the transaction commits). Listeners notify the provider
 * (a log-channel stub until the notification platform of P3.6) and, from P2.2, take the provider offline.
 */
final class ProviderStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly ProviderProfile $profile,
        public readonly ?ProviderStatus $from,
        public readonly ProviderStatus $to,
        public readonly ?string $reason,
    ) {}
}
