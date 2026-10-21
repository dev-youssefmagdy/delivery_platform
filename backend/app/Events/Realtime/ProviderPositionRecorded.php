<?php

namespace App\Events\Realtime;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A trusted position of an online provider was stored. Not broadcast itself: `PublishOperationsFeed` turns it into a
 * throttled `operations.provider_moved` for the admin map.
 */
final class ProviderPositionRecorded
{
    use Dispatchable;

    public function __construct(public readonly string $providerUuid, public readonly float $lat, public readonly float $lng, public readonly bool $busy) {}
}
