<?php

namespace App\Support\Config;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Cache of the assembled public app config. Every service that writes something the config exposes (settings,
 * countries, currencies, policy versions) calls forget(); the TTL is only a safety net.
 */
final class AppConfigCache
{
    public const KEY = 'app_config.payload.v2';

    public const TTL_SECONDS = 600;

    public function __construct(private readonly Repository $cache) {}

    /**
     * @param Closure(): array<string, mixed> $build
     * @return array<string, mixed>
     */
    public function remember(Closure $build): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->cache->remember(self::KEY, self::TTL_SECONDS, $build);

        return $payload;
    }

    public function forget(): void
    {
        $this->cache->forget(self::KEY);
    }
}
