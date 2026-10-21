<?php

namespace App\Repositories\Contracts\Location;

use App\DTOs\Location\LocationSample;
use App\Models\ProviderLastLocation;
use App\Models\ProviderProfile;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonInterface;

/**
 * One row per provider: the newest trusted position.
 */
interface ProviderLastLocationRepositoryInterface
{
    public function findFor(ProviderProfile $profile): ?ProviderLastLocation;

    public function upsert(ProviderProfile $profile, LocationSample $sample, ?int $cityId, CarbonInterface $receivedAt): ProviderLastLocation;

    public function deleteFor(ProviderProfile $profile): void;

    /**
     * Providers that are online with a position in the city no older than `$freshSince`, nearest first, within the
     * radius. Backs `DatabaseGeoIndex`.
     *
     * @return list<array{provider_profile_id: int, city_id: int, point: GeoPoint, recorded_at: CarbonInterface, accuracy_m: float|null, heading: int|null, speed_mps: float|null, distance_m: float}>
     */
    public function onlineNear(int $cityId, GeoPoint $centre, float $radiusMeters, CarbonInterface $freshSince, int $limit): array;
}
