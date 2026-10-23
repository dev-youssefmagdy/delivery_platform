<?php

namespace App\Repositories\Eloquent\Safety;

use App\Models\TrustedContact;
use App\Models\TrustedContactAction;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<TrustedContact>
 */
final class TrustedContactRepository extends BaseRepository implements TrustedContactRepositoryInterface
{
    public function __construct(TrustedContact $model)
    {
        parent::__construct($model);
    }

    public function forUser(int $userId): Collection
    {
        return $this->query()->where('user_id', $userId)->orderBy('id')->get()->toBase();
    }

    public function findOfUser(string $uuid, int $userId): ?TrustedContact
    {
        return $this->query()->where('uuid', $uuid)->where('user_id', $userId)->first();
    }

    public function countOfUser(int $userId): int
    {
        return $this->query()->where('user_id', $userId)->count();
    }

    public function phoneExists(int $userId, string $phoneHash, ?int $exceptId = null): bool
    {
        return $this->query()->where('user_id', $userId)->where('phone_hash', $phoneHash)->when($exceptId !== null, static fn ($query) => $query->whereKeyNot($exceptId))->exists();
    }

    public function recordAction(array $attributes): TrustedContactAction
    {
        return TrustedContactAction::query()->create($attributes);
    }

    public function actionsForIncident(int $incidentId): Collection
    {
        return TrustedContactAction::query()->where('incident_id', $incidentId)->orderBy('id')->get()->toBase();
    }

    public function tripSharesSince(int $userId, int $bookingId, CarbonInterface $since): int
    {
        return TrustedContactAction::query()->where('user_id', $userId)->where('booking_id', $bookingId)->where('action', 'trip_share')->where('created_at', '>=', $since)->count();
    }

    public function actionsOfUser(int $userId): Collection
    {
        return TrustedContactAction::query()->where('user_id', $userId)->orderBy('id')->get()->toBase();
    }

    public function purgeForUser(int $userId): array
    {
        $actions = TrustedContactAction::query()->where('user_id', $userId)->update(['contact_name' => null, 'contact_phone_masked' => null, 'trusted_contact_id' => null]);
        $contacts = $this->query()->where('user_id', $userId)->delete();

        return ['contacts' => (int) $contacts, 'actions' => (int) $actions];
    }
}
