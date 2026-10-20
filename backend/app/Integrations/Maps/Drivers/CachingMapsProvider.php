<?php

namespace App\Integrations\Maps\Drivers;

use App\DTOs\Geography\PlaceData;
use App\DTOs\Geography\RouteData;
use App\Enums\Geo\MapsProviderStatus;
use App\Integrations\Maps\Contracts\MapsProvider;
use App\Support\Geo\GeoPoint;
use Illuminate\Contracts\Cache\Repository;

/**
 * Caches successful answers of the real provider (TTLs in config/maps.php). Failures are never cached, so an
 * outage heals as soon as the provider does. Keys are rounded to ~1 m and include the locale.
 */
final class CachingMapsProvider implements MapsProvider
{
    public function __construct(private readonly MapsProvider $inner, private readonly Repository $cache) {}

    public function name(): string
    {
        return $this->inner->name();
    }

    public function status(): MapsProviderStatus
    {
        return $this->inner->status();
    }

    public function geocode(string $query, ?GeoPoint $near, string $locale): array
    {
        return $this->remember('geocode', [$query, $near?->key(), $locale], (int) config('maps.cache.geocode_ttl'), fn (): array => $this->inner->geocode($query, $near, $locale));
    }

    public function reverseGeocode(GeoPoint $point, string $locale): ?PlaceData
    {
        /** @var array{0: PlaceData|null} $wrapped */
        $wrapped = $this->remember('reverse', [$point->key(), $locale], (int) config('maps.cache.reverse_ttl'), fn (): array => [$this->inner->reverseGeocode($point, $locale)]);

        return $wrapped[0];
    }

    public function placesSearch(string $query, ?GeoPoint $near, string $locale, int $limit): array
    {
        return $this->remember('search', [mb_strtolower(trim($query)), $near?->key(), $locale, $limit], (int) config('maps.cache.search_ttl'), fn (): array => $this->inner->placesSearch($query, $near, $locale, $limit));
    }

    public function route(GeoPoint $origin, GeoPoint $destination, array $stops = []): RouteData
    {
        $key = [$origin->key(), $destination->key(), array_map(static fn (GeoPoint $p): string => $p->key(), $stops)];

        /** @var array{0: RouteData} $wrapped */
        $wrapped = $this->remember('route', $key, (int) config('maps.cache.route_ttl'), fn (): array => [$this->inner->route($origin, $destination, $stops)]);

        return $wrapped[0];
    }

    /**
     * @param array<int, mixed> $parts
     * @param callable(): array<int|string, mixed> $resolve
     * @return array<int|string, mixed>
     */
    private function remember(string $kind, array $parts, int $ttl, callable $resolve): array
    {
        if ($ttl <= 0) {
            return $resolve();
        }

        $key = 'maps:'.$this->inner->name().':'.$kind.':'.md5((string) json_encode($parts));
        $hit = $this->cache->get($key);

        if (is_array($hit)) {
            return $hit;
        }

        $value = $resolve();
        $this->cache->put($key, $value, $ttl);

        return $value;
    }
}
