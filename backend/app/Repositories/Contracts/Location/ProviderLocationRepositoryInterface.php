<?php

namespace App\Repositories\Contracts\Location;

use App\DTOs\Location\LocationSample;
use App\Enums\Location\LocationAnomaly;
use App\Models\ProviderLocation;
use App\Models\ProviderProfile;
use Carbon\CarbonInterface;

/**
 * The append-only location history.
 */
interface ProviderLocationRepositoryInterface
{
    public function append(ProviderProfile $profile, LocationSample $sample, ?LocationAnomaly $anomaly, ?int $bookingId): ProviderLocation;

    /**
     * `recorded_at` of the newest stored history row of the provider (used to sample the history).
     */
    public function latestRecordedAt(ProviderProfile $profile): ?CarbonInterface;

    /**
     * Deletes history older than the cut-off (rows without a booking only when `$withoutBooking`), at most `$limit` rows.
     */
    public function pruneBefore(CarbonInterface $before, bool $withoutBooking, int $limit): int;
}
