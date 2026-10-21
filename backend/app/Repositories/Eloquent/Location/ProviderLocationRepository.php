<?php

namespace App\Repositories\Eloquent\Location;

use App\DTOs\Location\LocationSample;
use App\Enums\Location\LocationAnomaly;
use App\Models\ProviderLocation;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Location\ProviderLocationRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * @extends BaseRepository<ProviderLocation>
 */
final class ProviderLocationRepository extends BaseRepository implements ProviderLocationRepositoryInterface
{
    public function __construct(ProviderLocation $model)
    {
        parent::__construct($model);
    }

    public function append(ProviderProfile $profile, LocationSample $sample, ?LocationAnomaly $anomaly, ?int $bookingId): ProviderLocation
    {
        $row = new ProviderLocation([
            'provider_profile_id' => $profile->id,
            'point' => $sample->point,
            'accuracy_m' => $sample->accuracyM,
            'heading' => $sample->heading,
            'speed_mps' => $sample->speedMps,
            'source' => $sample->source,
            'anomaly' => $anomaly,
            'booking_id' => $bookingId,
            'recorded_at' => $sample->recordedAt,
        ]);
        $row->save();

        return $row;
    }

    public function latestRecordedAt(ProviderProfile $profile): ?CarbonInterface
    {
        $value = $this->query()->where('provider_profile_id', $profile->id)->max('recorded_at');

        return $value === null ? null : CarbonImmutable::parse((string) $value, 'UTC');
    }

    public function pruneBefore(CarbonInterface $before, bool $withoutBooking, int $limit): int
    {
        return $this->query()
            ->where('recorded_at', '<', $before)
            ->when($withoutBooking, static fn ($query) => $query->whereNull('booking_id'))
            ->limit($limit)
            ->delete();
    }
}
