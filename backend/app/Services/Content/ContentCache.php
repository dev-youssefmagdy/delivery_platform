<?php

namespace App\Services\Content;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Cache of the public read side. Every key carries a version number; any content change bumps it, so a publish,
 * archive or edit is visible at once without tracking individual keys. Banner windows and scheduled publishing
 * are covered by the short TTL.
 */
final class ContentCache
{
    private const VERSION_KEY = 'content:version';

    public function __construct(private readonly Repository $cache) {}

    public function ttl(): int
    {
        return (int) config('content.cache_ttl_seconds', 60);
    }

    /**
     * @template T
     *
     * @param Closure(): T $callback
     * @return T
     */
    public function remember(string $key, Closure $callback): mixed
    {
        return $this->cache->remember('content:v'.$this->version().':'.$key, $this->ttl(), $callback);
    }

    public function bump(): void
    {
        $this->cache->forever(self::VERSION_KEY, $this->version() + 1);
    }

    private function version(): int
    {
        return (int) $this->cache->get(self::VERSION_KEY, 1);
    }
}
