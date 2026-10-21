<?php

namespace App\Services\Location\Index;

use App\DTOs\Location\NearbyProvider;
use App\DTOs\Location\ProviderPosition;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonInterface;

/**
 * Fast "which online providers are near here" lookup (Plan §2.3, ADR-03). The Redis implementation keeps a GEO set per
 * city plus a TTL'd hash per provider; the database implementation answers from `provider_last_locations` for tests and
 * local development without Redis. The index is a cache of live positions: eligibility is always re-checked by
 * `ProviderEligibilityService` before a provider is notified.
 */
interface ProviderGeoIndex
{
    /**
     * Adds or moves a provider. The entry expires after `$ttlSeconds` without another `put` (freshness).
     */
    public function put(ProviderPosition $position, int $ttlSeconds): void;

    public function remove(int $providerId): void;

    public function get(int $providerId): ?ProviderPosition;

    /**
     * Fresh providers of the city within the radius, nearest first.
     *
     * @return list<NearbyProvider>
     */
    public function nearby(int $cityId, GeoPoint $centre, float $radiusMeters, CarbonInterface $freshSince, int $limit): array;
}
