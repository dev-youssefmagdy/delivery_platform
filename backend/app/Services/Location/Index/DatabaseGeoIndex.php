<?php

namespace App\Services\Location\Index;

use App\DTOs\Location\NearbyProvider;
use App\DTOs\Location\ProviderPosition;
use App\Models\ProviderLastLocation;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Index backed by `provider_last_locations` (MySQL spatial functions). Writes are no-ops because the last position is
 * already persisted by `LocationIngestService` and the online flag is on the profile; category lists are not kept here
 * (dispatch checks the real vehicle and services anyway).
 */
final class DatabaseGeoIndex implements ProviderGeoIndex
{
    public function __construct(
        private readonly ProviderLastLocationRepositoryInterface $locations,
        private readonly ProviderProfileRepositoryInterface $profiles,
    ) {}

    public function put(ProviderPosition $position, int $ttlSeconds): void {}

    public function remove(int $providerId): void {}

    public function get(int $providerId): ?ProviderPosition
    {
        $profile = $this->profiles->find($providerId);
        $row = $profile === null || ! $profile->is_online ? null : $this->locations->findFor($profile);

        return $row === null ? null : $this->position($row);
    }

    public function nearby(int $cityId, GeoPoint $centre, float $radiusMeters, CarbonInterface $freshSince, int $limit): array
    {
        $hits = [];

        foreach ($this->locations->onlineNear($cityId, $centre, $radiusMeters, $freshSince, $limit) as $row) {
            $hits[] = new NearbyProvider(new ProviderPosition(
                providerId: $row['provider_profile_id'],
                cityId: $row['city_id'],
                point: $row['point'],
                recordedAt: CarbonImmutable::instance($row['recorded_at']),
                accuracyM: $row['accuracy_m'],
                heading: $row['heading'],
                speedMps: $row['speed_mps'],
            ), $row['distance_m']);
        }

        return $hits;
    }

    private function position(ProviderLastLocation $row): ProviderPosition
    {
        return new ProviderPosition(
            providerId: $row->provider_profile_id,
            cityId: (int) $row->city_id,
            point: $row->point,
            recordedAt: CarbonImmutable::instance($row->recorded_at),
            accuracyM: $row->accuracy_m,
            heading: $row->heading,
            speedMps: $row->speed_mps,
        );
    }
}
