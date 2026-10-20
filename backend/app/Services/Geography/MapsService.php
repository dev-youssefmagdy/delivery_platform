<?php

namespace App\Services\Geography;

use App\DTOs\Geography\PlaceData;
use App\DTOs\Geography\RouteData;
use App\Enums\Common\Locale;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Maps\Contracts\MapsProvider;
use App\Integrations\Maps\Exceptions\MapsUnavailableException;
use App\Integrations\Maps\Exceptions\NoRouteException;
use App\Support\Geo\GeoPoint;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Business-facing entry to the maps adapter: resolves the configured driver lazily (so an unconfigured platform
 * boots and answers `503 maps_unavailable` instead of failing) and maps adapter failures to stable error codes.
 * It never invents data: an outage is an error, not an empty result.
 */
final class MapsService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return list<PlaceData>
     */
    public function search(string $query, ?GeoPoint $near, ?int $limit = null): array
    {
        $query = $this->cleanQuery($query);
        $limit = max(1, min((int) config('maps.search.max_limit', 15), $limit ?? (int) config('maps.search.default_limit', 8)));

        return $this->call(fn (MapsProvider $maps): array => $maps->placesSearch($query, $near, $this->locale(), $limit));
    }

    /**
     * @return list<PlaceData>
     */
    public function geocode(string $query, ?GeoPoint $near = null): array
    {
        $query = $this->cleanQuery($query);

        return $this->call(fn (MapsProvider $maps): array => $maps->geocode($query, $near, $this->locale()));
    }

    public function reverse(GeoPoint $point): ?PlaceData
    {
        return $this->call(fn (MapsProvider $maps): ?PlaceData => $maps->reverseGeocode($point, $this->locale()));
    }

    /**
     * @param list<GeoPoint> $stops
     */
    public function route(GeoPoint $origin, GeoPoint $destination, array $stops = []): RouteData
    {
        if (count($stops) > (int) config('maps.route.max_stops', 5)) {
            throw BusinessRuleViolation::make('route_too_many_stops', 'stops');
        }

        return $this->call(fn (MapsProvider $maps): RouteData => $maps->route($origin, $destination, $stops));
    }

    public function provider(): ?MapsProvider
    {
        try {
            return $this->container->make(MapsProvider::class);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @template T
     *
     * @param callable(MapsProvider): T $request
     * @return T
     */
    private function call(callable $request): mixed
    {
        $provider = $this->provider();

        if ($provider === null) {
            throw ServiceUnavailableException::make('maps_unavailable');
        }

        try {
            return $request($provider);
        } catch (MapsUnavailableException $e) {
            Log::warning('maps.unavailable', ['driver' => $provider->name(), 'reason' => $e->getMessage()]);

            throw ServiceUnavailableException::make('maps_unavailable');
        } catch (NoRouteException) {
            throw BusinessRuleViolation::make('route_not_found');
        }
    }

    private function cleanQuery(string $query): string
    {
        $query = trim((string) preg_replace('/\s+/u', ' ', $query));

        if (mb_strlen($query) < (int) config('maps.search.min_query_length', 2)) {
            throw BusinessRuleViolation::make('maps_query_too_short', 'q');
        }

        return mb_substr($query, 0, (int) config('maps.search.max_query_length', 120));
    }

    private function locale(): string
    {
        $locale = Locale::tryFrom(app()->getLocale());

        return $locale instanceof Locale ? $locale->value : Locale::Arabic->value;
    }
}
