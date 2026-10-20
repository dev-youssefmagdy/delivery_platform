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
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Google Maps Platform: Geocoding API (geocode / reverse), Places API (New) Text Search (type-ahead) and Routes
 * API (computeRoutes). The key comes from MAPS_API_KEY only and is sent in a header, never logged.
 *
 * IMPLEMENTED FROM THE VENDOR DOCUMENTATION AND NOT TESTED AGAINST THE LIVE SERVICE (no credentials in this
 * repository): enable the three APIs for the key, restrict it by IP, and verify one search, one reverse geocode
 * and one route on staging before go-live (docs/INTEGRATIONS.md). Covered here by Http::fake tests.
 */
final class GoogleMapsProvider implements MapsProvider
{
    private const GEOCODING = 'https://maps.googleapis.com/maps/api/geocode/json';

    private const PLACES_SEARCH = 'https://places.googleapis.com/v1/places:searchText';

    private const ROUTES = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public function __construct(private readonly HttpFactory $http) {}

    public function name(): string
    {
        return 'google';
    }

    public function status(): MapsProviderStatus
    {
        return $this->key() === '' ? MapsProviderStatus::NotConfigured : MapsProviderStatus::Ok;
    }

    public function geocode(string $query, ?GeoPoint $near, string $locale): array
    {
        $params = ['address' => $query, 'language' => $locale, 'region' => (string) config('maps.google.region', '')];

        if ($near !== null) {
            $params['bounds'] = sprintf('%.5F,%.5F|%.5F,%.5F', $near->lat - 0.4, $near->lng - 0.4, $near->lat + 0.4, $near->lng + 0.4);
        }

        $body = $this->geocoding($params);
        $places = [];

        foreach ((array) ($body['results'] ?? []) as $result) {
            if (is_array($result) && ($place = $this->geocodedPlace($result)) !== null) {
                $places[] = $place;
            }
        }

        return $places;
    }

    public function reverseGeocode(GeoPoint $point, string $locale): ?PlaceData
    {
        $body = $this->geocoding(['latlng' => sprintf('%.7F,%.7F', $point->lat, $point->lng), 'language' => $locale]);
        $first = $body['results'][0] ?? null;

        return is_array($first) ? $this->geocodedPlace($first, $point) : null;
    }

    public function placesSearch(string $query, ?GeoPoint $near, string $locale, int $limit): array
    {
        $payload = [
            'textQuery' => $query,
            'languageCode' => $locale,
            'regionCode' => strtoupper((string) config('maps.google.region', '')) ?: null,
            'pageSize' => max(1, min(20, $limit)),
        ];

        if ($near !== null) {
            $payload['locationBias'] = ['circle' => ['center' => ['latitude' => $near->lat, 'longitude' => $near->lng], 'radius' => 30000.0]];
        }

        $response = $this->send(fn (PendingRequest $client): Response => $client
            ->withHeaders(['X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.types'])
            ->post(self::PLACES_SEARCH, array_filter($payload, static fn (mixed $v): bool => $v !== null)));

        $places = [];

        foreach ((array) $response->json('places', []) as $row) {
            $point = is_array($row) ? GeoPoint::tryMake($row['location']['latitude'] ?? null, $row['location']['longitude'] ?? null) : null;

            if ($point === null) {
                continue;
            }

            $types = (array) ($row['types'] ?? []);
            $precision = in_array('street_address', $types, true) || in_array('premise', $types, true)
                ? LocationPrecision::Exact
                : LocationPrecision::Street;
            $address = (string) ($row['formattedAddress'] ?? '');
            $title = (string) ($row['displayName']['text'] ?? explode(',', $address)[0]);

            $places[] = new PlaceData(isset($row['id']) ? (string) $row['id'] : null, $title, $address, $point, $precision, $this->name());
        }

        return $places;
    }

    public function route(GeoPoint $origin, GeoPoint $destination, array $stops = []): RouteData
    {
        $waypoint = static fn (GeoPoint $p): array => ['location' => ['latLng' => ['latitude' => $p->lat, 'longitude' => $p->lng]]];
        $payload = [
            'origin' => $waypoint($origin),
            'destination' => $waypoint($destination),
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE',
            'polylineQuality' => 'OVERVIEW',
        ];

        if ($stops !== []) {
            $payload['intermediates'] = array_map($waypoint, $stops);
        }

        $response = $this->send(fn (PendingRequest $client): Response => $client
            ->withHeaders(['X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline'])
            ->post(self::ROUTES, $payload), allow: [404]);

        $route = $response->json('routes.0');

        if (! is_array($route)) {
            if ($response->status() === 404 || $response->json('routes') === null || $response->json('routes') === []) {
                throw new NoRouteException('Google found no route.');
            }

            throw MapsUnavailableException::because('Google Routes returned an unexpected answer.');
        }

        if (! isset($route['distanceMeters'], $route['duration'], $route['polyline']['encodedPolyline'])) {
            throw MapsUnavailableException::because('Google Routes returned an incomplete answer.');
        }

        return new RouteData((int) $route['distanceMeters'], (int) rtrim((string) $route['duration'], 's'), (string) $route['polyline']['encodedPolyline'], $this->name());
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function geocoding(array $params): array
    {
        $params = array_filter($params, static fn (mixed $v): bool => $v !== '' && $v !== null);
        $response = $this->send(fn (PendingRequest $client): Response => $client->get(self::GEOCODING, $params + ['key' => $this->key()]));
        $status = (string) $response->json('status');

        return match ($status) {
            'OK', 'ZERO_RESULTS' => (array) $response->json(),
            default => throw MapsUnavailableException::because('Google Geocoding answered '.($status === '' ? 'an unexpected body' : $status).'.'),
        };
    }

    /**
     * @param array<string, mixed> $result
     */
    private function geocodedPlace(array $result, ?GeoPoint $fallback = null): ?PlaceData
    {
        $point = GeoPoint::tryMake($result['geometry']['location']['lat'] ?? null, $result['geometry']['location']['lng'] ?? null) ?? $fallback;

        if ($point === null) {
            return null;
        }

        $locationType = (string) ($result['geometry']['location_type'] ?? '');
        $types = (array) ($result['types'] ?? []);
        $precision = match (true) {
            $locationType === 'ROOFTOP' => LocationPrecision::Exact,
            in_array('route', $types, true) || in_array('street_address', $types, true) => LocationPrecision::Street,
            default => LocationPrecision::Approximate,
        };
        $address = (string) ($result['formatted_address'] ?? '');

        return new PlaceData(isset($result['place_id']) ? (string) $result['place_id'] : null, trim(explode(',', $address)[0]), $address, $point, $precision, $this->name());
    }

    /**
     * @param callable(PendingRequest): Response $request
     * @param array<int, int> $allow
     */
    private function send(callable $request, array $allow = []): Response
    {
        if ($this->key() === '') {
            throw MapsUnavailableException::because('The Google Maps API key is not configured.');
        }

        try {
            $client = $this->http
                ->timeout((int) config('maps.google.timeout_seconds', 8))
                ->withHeaders(['X-Goog-Api-Key' => $this->key()])
                ->acceptJson();
            /** @var Response $response */
            $response = $request($client);
        } catch (Throwable) {
            // The original exception can embed the request URL (and with it the key): do not chain it.
            throw MapsUnavailableException::because('The maps provider could not be reached.');
        }

        if ($response->successful() || in_array($response->status(), $allow, true)) {
            return $response;
        }

        // 403 (key/API disabled), 429 (quota) and 5xx are all "the maps service cannot serve us right now".
        throw MapsUnavailableException::because('The maps provider answered HTTP '.$response->status().'.');
    }

    private function key(): string
    {
        return (string) config('maps.google.api_key', '');
    }
}
