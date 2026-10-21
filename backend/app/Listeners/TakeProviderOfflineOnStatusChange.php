<?php

namespace App\Listeners;

use App\Enums\Location\OfflineReason;
use App\Enums\Provider\ProviderStatus;
use App\Events\ProviderStatusChanged;
use App\Services\Provider\AvailabilityService;

/**
 * A provider that is no longer approved (suspended, deletion pending, sent back to review) must leave the geo index
 * and stop receiving requests immediately.
 */
final class TakeProviderOfflineOnStatusChange
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function handle(ProviderStatusChanged $event): void
    {
        if ($event->from === ProviderStatus::Approved && $event->to !== ProviderStatus::Approved) {
            $this->availability->forceOffline($event->profile, OfflineReason::AccountChanged);
        }
    }
}
