<?php

namespace App\Repositories\Eloquent\Location;

use App\DTOs\Location\LocationSample;
use App\Enums\Location\LocationAnomaly;
use App\Enums\Support\TicketStatus;
use App\Models\ProviderLocation;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Location\ProviderLocationRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

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
            // Legal hold (ADR-171): samples of a booking with a held safety incident or an open ticket stay as evidence.
            ->where(static fn ($query) => $query->whereNull('booking_id')->orWhere(static fn ($held) => $held
                ->whereNotIn('booking_id', DB::table('safety_incidents')->select('booking_id')->where('legal_hold', true)->whereNotNull('booking_id'))
                ->whereNotIn('booking_id', DB::table('support_tickets')->select('booking_id')->whereNotNull('booking_id')->whereIn('status', array_map(static fn (TicketStatus $status): string => $status->value, TicketStatus::activeCases())))))
            ->limit($limit)
            ->delete();
    }
}
