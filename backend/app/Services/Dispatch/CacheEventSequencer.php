<?php

namespace App\Services\Dispatch;

use Illuminate\Contracts\Cache\Repository;

final class CacheEventSequencer implements EventSequencer
{
    private const TTL_SECONDS = 86_400;

    public function __construct(private readonly Repository $cache) {}

    public function next(string $bookingUuid): int
    {
        $key = "booking-event-sequence:{$bookingUuid}";
        $this->cache->add($key, 0, self::TTL_SECONDS);

        return (int) $this->cache->increment($key);
    }
}
