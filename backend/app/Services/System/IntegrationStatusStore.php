<?php

namespace App\Services\System;

use App\Enums\System\HealthArea;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Throwable;

/**
 * Remembers the last failure of each external adapter for the health page (SRS 16.15). Only a safe code is kept (the exception
 * class name or an error code), never the provider's message, which can carry addresses or credentials. A later success clears it.
 */
final class IntegrationStatusStore
{
    private const TTL_SECONDS = 86400;

    public function __construct(private readonly CacheRepository $cache) {}

    public function recordFailure(HealthArea $area, Throwable|string $reason): void
    {
        $code = $reason instanceof Throwable ? class_basename($reason) : preg_replace('/[^A-Za-z0-9_.:-]/', '', substr($reason, 0, 64));
        $this->cache->put($this->key($area), ['code' => $code, 'at' => now()->getTimestamp()], self::TTL_SECONDS);
    }

    public function recordSuccess(HealthArea $area): void
    {
        $this->cache->forget($this->key($area));
    }

    /**
     * @return array{code: string, at: CarbonImmutable}|null
     */
    public function lastFailure(HealthArea $area): ?array
    {
        $entry = $this->cache->get($this->key($area));

        if (! is_array($entry) || ! isset($entry['code'], $entry['at'])) {
            return null;
        }

        return ['code' => (string) $entry['code'], 'at' => CarbonImmutable::createFromTimestamp((int) $entry['at'])];
    }

    /**
     * Fire-and-forget helper for adapter call sites: reporting must never break the delivery path.
     */
    public static function failed(HealthArea $area, Throwable|string $reason): void
    {
        try {
            app(self::class)->recordFailure($area, $reason);
        } catch (Throwable) {
            // Health bookkeeping is best effort.
        }
    }

    public static function succeeded(HealthArea $area): void
    {
        try {
            app(self::class)->recordSuccess($area);
        } catch (Throwable) {
            // Best effort.
        }
    }

    private function key(HealthArea $area): string
    {
        return 'integration:last_failure:'.$area->value;
    }
}
