<?php

namespace App\Support\Catalog;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Cache of the PUBLIC, position-less catalog reads (service families, categories, vehicle categories). Keys carry a version
 * number; every save or delete of a catalog model bumps it (listeners in AppServiceProvider), which orphans all entries at once.
 * The TTL is only a safety net for writes that bypass Eloquent. Never used for anything per-user, authorization or money.
 */
final class CatalogCache
{
    private const VERSION_KEY = 'catalog.version';

    public const TTL_SECONDS = 300;

    public function __construct(private readonly Repository $cache) {}

    /**
     * @template T
     * @param Closure(): T $build
     * @return T
     */
    public function remember(string $name, Closure $build): mixed
    {
        return $this->cache->remember('catalog.v'.$this->version().'.'.$name, self::TTL_SECONDS, $build);
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
