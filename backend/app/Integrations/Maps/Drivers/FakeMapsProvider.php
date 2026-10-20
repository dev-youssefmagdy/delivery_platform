<?php

namespace App\Integrations\Maps\Drivers;

use App\DTOs\Geography\PlaceData;
use App\DTOs\Geography\RouteData;
use App\Enums\Geo\LocationPrecision;
use App\Enums\Geo\MapsProviderStatus;
use App\Integrations\Maps\Contracts\MapsProvider;
use App\Integrations\Maps\Exceptions\MapsUnavailableException;
use App\Integrations\Maps\Exceptions\NoRouteException;
use App\Integrations\Maps\Polyline;
use App\Support\Geo\Distance;
use App\Support\Geo\GeoPoint;

/**
 * Deterministic offline driver for tests and local development (refused outside local/testing by the boot
 * guard). Searches a fixed list of Cairo landmarks; routes are the straight line scaled by 1.3 at 30 km/h.
 * The query `__unavailable__` simulates a provider outage and `__noroute__` as a destination query is not
 * supported: use a destination at latitude 0.5 (`GeoPoint` 0.5, 0.5) to simulate "no route".
 */
final class FakeMapsProvider implements MapsProvider
{
    public const OUTAGE_QUERY = '__unavailable__';

    public const NO_ROUTE_LAT = 0.5;

    /** @var list<array{id: string, title: string, address: string, lat: float, lng: float}> */
    private const PLACES = [
        ['id' => 'fake-tahrir', 'title' => 'Tahrir Square', 'address' => 'Tahrir Square, Downtown, Cairo', 'lat' => 30.0444, 'lng' => 31.2357],
        ['id' => 'fake-citadel', 'title' => 'Cairo Citadel', 'address' => 'Salah Salem St, Cairo', 'lat' => 30.0287, 'lng' => 31.2599],
        ['id' => 'fake-pyramids', 'title' => 'Giza Pyramids', 'address' => 'Al Haram, Giza', 'lat' => 29.9792, 'lng' => 31.1342],
        ['id' => 'fake-airport', 'title' => 'Cairo Airport', 'address' => 'Cairo International Airport, Heliopolis', 'lat' => 30.1219, 'lng' => 31.4056],
        ['id' => 'fake-zamalek', 'title' => 'Zamalek', 'address' => '26th of July St, Zamalek, Cairo', 'lat' => 30.0626, 'lng' => 31.2197],
    ];

    public function name(): string
    {
        return 'fake';
    }

    public function status(): MapsProviderStatus
    {
        return MapsProviderStatus::Ok;
    }

    public function geocode(string $query, ?GeoPoint $near, string $locale): array
    {
        return $this->search($query, $near, 5);
    }

    public function reverseGeocode(GeoPoint $point, string $locale): PlaceData
    {
        return new PlaceData(null, sprintf('%.4F, %.4F', $point->lat, $point->lng), 'Near '.$point->key(), $point, LocationPrecision::Approximate, $this->name());
    }

    public function placesSearch(string $query, ?GeoPoint $near, string $locale, int $limit): array
    {
        return $this->search($query, $near, $limit);
    }

    public function route(GeoPoint $origin, GeoPoint $destination, array $stops = []): RouteData
    {
        if (abs($destination->lat - self::NO_ROUTE_LAT) < 1e-9) {
            throw new NoRouteException('No route in the fake provider.');
        }

        $path = [$origin, ...$stops, $destination];
        $meters = (int) round(Distance::pathMeters($path) * 1.3);

        return new RouteData($meters, (int) round($meters / (30000 / 3600)), Polyline::encode($path), $this->name());
    }

    /**
     * @return list<PlaceData>
     */
    private function search(string $query, ?GeoPoint $near, int $limit): array
    {
        $needle = mb_strtolower(trim($query));

        if ($needle === self::OUTAGE_QUERY) {
            throw MapsUnavailableException::because('Simulated maps outage.');
        }

        $matches = array_values(array_filter(
            self::PLACES,
            static fn (array $place): bool => $needle !== '' && (str_contains(mb_strtolower($place['title']), $needle) || str_contains(mb_strtolower($place['address']), $needle)),
        ));

        if ($near !== null) {
            usort($matches, static fn (array $a, array $b): int => Distance::haversineMeters($near, GeoPoint::make($a['lat'], $a['lng'])) <=> Distance::haversineMeters($near, GeoPoint::make($b['lat'], $b['lng'])));
        }

        return array_map(
            fn (array $place): PlaceData => new PlaceData($place['id'], $place['title'], $place['address'], GeoPoint::make($place['lat'], $place['lng']), LocationPrecision::Street, $this->name()),
            array_slice($matches, 0, $limit),
        );
    }
}
