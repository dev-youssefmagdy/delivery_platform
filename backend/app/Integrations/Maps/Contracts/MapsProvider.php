<?php

namespace App\Integrations\Maps\Contracts;

use App\DTOs\Geography\PlaceData;
use App\DTOs\Geography\RouteData;
use App\Enums\Geo\MapsProviderStatus;
use App\Integrations\Maps\Exceptions\MapsUnavailableException;
use App\Integrations\Maps\Exceptions\NoRouteException;
use App\Support\Geo\GeoPoint;

/**
 * The only way the platform reaches a geocoder / router (ADR-12, SRS §22). Implementations map every
 * transport, quota or credential problem to MapsUnavailableException and never return invented data.
 */
interface MapsProvider
{
    /**
     * Short driver name stored with results (`osrm_nominatim`, `google`, `fake`).
     */
    public function name(): string;

    /**
     * Configuration-level status without a network call (credentials/URLs present).
     */
    public function status(): MapsProviderStatus;

    /**
     * Address text to positions.
     *
     * @return list<PlaceData>
     *
     * @throws MapsUnavailableException
     */
    public function geocode(string $query, ?GeoPoint $near, string $locale): array;

    /**
     * Position to the nearest address; null when the provider knows nothing there.
     *
     * @throws MapsUnavailableException
     */
    public function reverseGeocode(GeoPoint $point, string $locale): ?PlaceData;

    /**
     * Type-ahead search for places and addresses, ranked near `$near` when given.
     *
     * @return list<PlaceData>
     *
     * @throws MapsUnavailableException
     */
    public function placesSearch(string $query, ?GeoPoint $near, string $locale, int $limit): array;

    /**
     * Driving route through the optional stops (in order).
     *
     * @param list<GeoPoint> $stops
     *
     * @throws MapsUnavailableException
     * @throws NoRouteException
     */
    public function route(GeoPoint $origin, GeoPoint $destination, array $stops = []): RouteData;
}
