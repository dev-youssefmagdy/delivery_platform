<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Scheduled every minute; the health check reports the scheduler as failing when the heartbeat is stale.
 */
#[Signature('system:heartbeat')]
#[Description('Record a scheduler heartbeat for the health check')]
class SystemHeartbeat extends Command
{
    public function handle(CacheRepository $cache): int
    {
        $cache->forever((string) config('health.heartbeat_cache_key'), now()->getTimestamp());

        $this->components->info('Heartbeat recorded.');

        return self::SUCCESS;
    }
}
