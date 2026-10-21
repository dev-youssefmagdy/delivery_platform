<?php

namespace App\Repositories\Eloquent\Location;

use App\DTOs\Location\LocationSample;
use App\Models\ProviderLastLocation;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * @extends BaseRepository<ProviderLastLocation>
 */
final class ProviderLastLocationRepository extends BaseRepository implements ProviderLastLocationRepositoryInterface
{
    public function __construct(ProviderLastLocation $model)
    {
        parent::__construct($model);
    }

    public function findFor(ProviderProfile $profile): ?ProviderLastLocation
    {
        return $this->query()->where('provider_profile_id', $profile->id)->first();
    }

    public function upsert(ProviderProfile $profile, LocationSample $sample, ?int $cityId, CarbonInterface $receivedAt): ProviderLastLocation
    {
        $row = $this->findFor($profile) ?? new ProviderLastLocation(['provider_profile_id' => $profile->id]);
        $row->fill([
            'city_id' => $cityId,
            'point' => $sample->point,
            'accuracy_m' => $sample->accuracyM,
            'heading' => $sample->heading,
            'speed_mps' => $sample->speedMps,
            'source' => $sample->source,
            'recorded_at' => $sample->recordedAt,
            'received_at' => $receivedAt,
        ])->save();

        return $row;
    }

    public function deleteFor(ProviderProfile $profile): void
    {
        $this->query()->where('provider_profile_id', $profile->id)->delete();
    }

    public function onlineNear(int $cityId, GeoPoint $centre, float $radiusMeters, CarbonInterface $freshSince, int $limit): array
    {
        $distance = "ST_Distance_Sphere(provider_last_locations.point, ST_GeomFromText(?, 4326, 'axis-order=long-lat'))";

        $rows = $this->query()
            ->join('provider_profiles', 'provider_profiles.id', '=', 'provider_last_locations.provider_profile_id')
            ->where('provider_profiles.is_online', true)
            ->where('provider_last_locations.city_id', $cityId)
            ->where('provider_last_locations.recorded_at', '>=', $freshSince)
            ->whereRaw("{$distance} <= ?", [$centre->toWkt(), $radiusMeters])
            ->orderByRaw("{$distance} asc", [$centre->toWkt()])
            ->limit($limit)
            ->select('provider_last_locations.*')
            ->selectRaw("{$distance} as distance_m", [$centre->toWkt()])
            ->get();

        return $rows->map(static fn (ProviderLastLocation $row): array => [
            'provider_profile_id' => $row->provider_profile_id,
            'city_id' => (int) $row->city_id,
            'point' => $row->point,
            'recorded_at' => CarbonImmutable::instance($row->recorded_at),
            'accuracy_m' => $row->accuracy_m,
            'heading' => $row->heading,
            'speed_mps' => $row->speed_mps,
            'distance_m' => (float) $row->getAttribute('distance_m'),
        ])->all();
    }
}
