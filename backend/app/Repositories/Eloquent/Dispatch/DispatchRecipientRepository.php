<?php

namespace App\Repositories\Eloquent\Dispatch;

use App\Enums\Dispatch\DispatchResponse;
use App\Models\DispatchRecipient;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<DispatchRecipient>
 */
final class DispatchRecipientRepository extends BaseRepository implements DispatchRecipientRepositoryInterface
{
    public function __construct(DispatchRecipient $model)
    {
        parent::__construct($model);
    }

    public function providerIdsFor(int $bookingId): array
    {
        return array_map('intval', $this->query()->where('booking_id', $bookingId)->pluck('provider_profile_id')->all());
    }

    public function recordWave(int $bookingId, int $wave, array $rows): array
    {
        $recorded = [];

        foreach ($rows as $row) {
            $recipient = $this->query()->firstOrCreate(
                ['booking_id' => $bookingId, 'provider_profile_id' => $row['provider_profile_id']],
                ['wave' => $wave, 'distance_m' => $row['distance_m'], 'notified_at' => now()],
            );

            if ($recipient->wasRecentlyCreated) {
                $recorded[] = $row['provider_profile_id'];
            }
        }

        return $recorded;
    }

    public function findRecipient(int $bookingId, int $providerProfileId): ?DispatchRecipient
    {
        return $this->query()->where('booking_id', $bookingId)->where('provider_profile_id', $providerProfileId)->first();
    }

    public function markSeen(int $bookingId, int $providerProfileId): void
    {
        $this->query()->where('booking_id', $bookingId)->where('provider_profile_id', $providerProfileId)->whereNull('seen_at')->update(['seen_at' => now()]);
    }

    public function respond(int $bookingId, int $providerProfileId, DispatchResponse $response): void
    {
        $this->query()->where('booking_id', $bookingId)->where('provider_profile_id', $providerProfileId)
            ->update(['response' => $response->value, 'responded_at' => now()]);
    }

    public function forBooking(int $bookingId): Collection
    {
        return $this->query()->where('booking_id', $bookingId)->orderBy('wave')->orderBy('distance_m')->get();
    }

    public function countForWave(int $bookingId, int $wave): int
    {
        return $this->query()->where('booking_id', $bookingId)->where('wave', $wave)->count();
    }

    public function clearForBooking(int $bookingId, array $keepDeclinedProviderIds = []): void
    {
        $this->query()->where('booking_id', $bookingId)->when($keepDeclinedProviderIds !== [], static fn ($query) => $query->whereNotIn('provider_profile_id', $keepDeclinedProviderIds))->delete();

        if ($keepDeclinedProviderIds !== []) {
            $this->query()->where('booking_id', $bookingId)->whereIn('provider_profile_id', $keepDeclinedProviderIds)
                ->update(['response' => DispatchResponse::Declined->value, 'responded_at' => now()]);
        }
    }

    public function lastWave(int $bookingId): int
    {
        return (int) DispatchRecipient::query()->where('booking_id', $bookingId)->max('wave');
    }

    public function datatableQuery(int $bookingId): Builder
    {
        return DispatchRecipient::query()
            ->with('providerProfile.user:id,uuid,name,phone_e164')
            ->where('booking_id', $bookingId);
    }
}
