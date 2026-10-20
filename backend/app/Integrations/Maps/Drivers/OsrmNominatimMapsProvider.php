<?php

namespace App\Integrations\Maps\Drivers;

use App\DTOs\Geography\PlaceData;
use App\DTOs\Geography\RouteData;
use App\Enums\Geo\LocationPrecision;
use App\Enums\Geo\MapsProviderStatus;
use App\Integrations\Maps\Contracts\MapsProvider;
use App\Integrations\Maps\Exceptions\MapsUnavailableException;
use App\Integrations\Maps\Exceptions\NoRouteException;
use App\Support\Geo\GeoPoint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * OpenStreetMap stack: Nominatim for geocoding / reverse / search and OSRM for routes. Base URLs are
 * configurable (config/maps.php) so production points at self-hosted instances; the public servers are for
 * development only. Nominatim requests are spaced by `min_interval_ms` (their usage policy: 1 request/second)
 * and carry an identifying User-Agent. Not exercised against the live servers here (no network in the build
 * environment); the HTTP shapes follow the documented APIs and are covered by Http::fake tests.
 */
final class OsrmNominatimMapsProvider implements MapsProvider
{
    private const THROTTLE_KEY = 'maps:nominatim:last_request_at';

    public function __construct(private readonly HttpFactory $http) {}

    public function name(): string
    {
        return 'osrm_nominatim';
    }

    public function status(): MapsProviderStatus
    {
        return $this->cfg('nominatim_url') !== '' && $this->cfg('osrm_url') !== '' && $this->cfg('user_agent') !== ''
            ? MapsProviderStatus::Ok
            : MapsProviderStatus::NotConfigured;
    }

    public function geocode(string $query, ?GeoPoint $near, string $locale): array
    {
        return $this->search($query, $near, $locale, 5);
    }

    public function placesSearch(string $query, ?GeoPoint $near, string $locale, int $limit): array
    {
        return $this->search($query, $near, $locale, $limit);
    }

    public function reverseGeocode(GeoPoint $point, string $locale): ?PlaceData
    {
        $this->assertConfigured();
        $response = $this->nominatim('/reverse', [
            'lat' => $point->lat,
            'lon' => $point->lng,
            'format' => 'jsonv2',
            'zoom' => 18,
            'addressdetails' => 1,
            'accept-language' => $locale,
        ]);

        $body = $response->json();

        if (! is_array($body) || isset($body['error'])) {
            return null;
        }

        return $this->place($body, $point);
    }

    public function route(GeoPoint $origin, GeoPoint $destination, array $stops = []): RouteData
    {
        $this->assertConfigured();
        $coordinates = implode(';', array_map(
            static fn (GeoPoint $p): string => sprintf('%.6F,%.6F', $p->lng, $p->lat),
            [$origin, ...$stops, $destination],
        ));

        $response = $this->send(fn (): Response => $this->client()->get(rtrim($this->cfg('osrm_url'), '/').'/route/v1/driving/'.$coordinates, [
            'overview' => 'full',
            'geometries' => 'polyline',
            'steps' => 'false',
            'alternatives' => 'false',
        ]), allow: [400]);

        $code = $response->json('code');

        if ($code === 'NoRoute' || $code === 'NoSegment') {
            throw new NoRouteException('OSRM found no route.');
        }

        $route = $response->json('routes.0');

        if ($code !== 'Ok' || ! is_array($route) || ! isset($route['distance'], $route['duration'], $route['geometry'])) {
            throw MapsUnavailableException::because('OSRM returned an unexpected answer.');
        }

        return new RouteData((int) round((float) $route['distance']), (int) round((float) $route['duration']), (string) $route['geometry'], $this->name());
    }

    /**
     * @return list<PlaceData>
     */
    private function search(string $query, ?GeoPoint $near, string $locale, int $limit): array
    {
        $this->assertConfigured();
        $params = [
            'q' => $query,
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'limit' => max(1, $limit),
            'accept-language' => $locale,
        ];

        if ($this->cfg('country_codes') !== '') {
            $params['countrycodes'] = $this->cfg('country_codes');
        }

        if ($near !== null) {
            // A bias, not a restriction: Nominatim ranks results inside the box first.
            $params['viewbox'] = sprintf('%.5F,%.5F,%.5F,%.5F', $near->lng - 0.4, $near->lat + 0.4, $near->lng + 0.4, $near->lat - 0.4);
            $params['bounded'] = 0;
        }

        $body = $this->nominatim('/search', $params)->json();

        if (! is_array($body)) {
            throw MapsUnavailableException::because('Nominatim returned an unexpected answer.');
        }

        $places = [];

        foreach ($body as $row) {
            if (is_array($row) && ($place = $this->place($row, null)) !== null) {
                $places[] = $place;
            }
        }

        return $places;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function place(array $row, ?GeoPoint $fallback): ?PlaceData
    {
        $point = GeoPoint::tryMake($row['lat'] ?? null, $row['lon'] ?? null) ?? $fallback;

        if ($point === null) {
            return null;
        }

        $address = is_array($row['address'] ?? null) ? $row['address'] : [];
        $display = (string) ($row['display_name'] ?? '');
        $road = (string) ($address['road'] ?? $address['pedestrian'] ?? '');
        $title = trim((string) ($row['name'] ?? ''));

        if ($title === '') {
            $title = trim(($address['house_number'] ?? '').' '.$road);
        }

        if ($title === '') {
            $title = trim(explode(',', $display)[0]);
        }

        $rank = (int) ($row['place_rank'] ?? 0);
        $precision = match (true) {
            isset($address['house_number']) || in_array($row['addresstype'] ?? null, ['house', 'building'], true) => LocationPrecision::Exact,
            $rank >= 26 => LocationPrecision::Street,
            default => LocationPrecision::Approximate,
        };

        return new PlaceData(isset($row['place_id']) ? (string) $row['place_id'] : null, $title, $display, $point, $precision, $this->name());
    }

    /**
     * @param array<string, mixed> $query
     */
    private function nominatim(string $path, array $query): Response
    {
        $email = $this->cfg('contact_email');

        if ($email !== '') {
            $query['email'] = $email;
        }

        $this->throttle();

        return $this->send(fn (): Response => $this->client()->get(rtrim($this->cfg('nominatim_url'), '/').$path, $query));
    }

    /**
     * @param array<int, int> $allow extra HTTP statuses that carry a meaningful body (handled by the caller)
     */
    private function send(callable $request, array $allow = []): Response
    {
        try {
            /** @var Response $response */
            $response = $request();
        } catch (ConnectionException $e) {
            throw MapsUnavailableException::because('The maps provider could not be reached.', $e);
        } catch (Throwable $e) {
            throw MapsUnavailableException::because('The maps request failed.', $e);
        }

        if ($response->successful() || in_array($response->status(), $allow, true)) {
            return $response;
        }

        throw MapsUnavailableException::because('The maps provider answered HTTP '.$response->status().'.');
    }

    private function client(): PendingRequest
    {
        return $this->http
            ->timeout((int) config('maps.osrm_nominatim.timeout_seconds', 8))
            ->withUserAgent($this->cfg('user_agent'))
            ->acceptJson();
    }

    /**
     * Keeps at least `min_interval_ms` between two Nominatim calls across workers (cache lock).
     */
    private function throttle(): void
    {
        $interval = (int) config('maps.osrm_nominatim.min_interval_ms', 0);

        if ($interval <= 0) {
            return;
        }

        try {
            Cache::lock('maps:nominatim:throttle', 10)->block(5, function () use ($interval): void {
                $last = (float) Cache::get(self::THROTTLE_KEY, 0.0);
                $wait = (int) ceil($interval - (microtime(true) - $last) * 1000);

                if ($wait > 0) {
                    usleep($wait * 1000);
                }

                Cache::put(self::THROTTLE_KEY, microtime(true), 60);
            });
        } catch (Throwable) {
            // Could not get the lock in time: refuse instead of hammering the provider.
            throw MapsUnavailableException::because('The maps provider is rate limited locally.');
        }
    }

    private function assertConfigured(): void
    {
        if ($this->status() !== MapsProviderStatus::Ok) {
            throw MapsUnavailableException::because('The OSRM/Nominatim maps provider is not configured.');
        }
    }

    private function cfg(string $key): string
    {
        return (string) config("maps.osrm_nominatim.{$key}", '');
    }
}
