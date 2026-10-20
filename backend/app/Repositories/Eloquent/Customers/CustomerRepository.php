<?php

namespace App\Repositories\Eloquent\Customers;

use App\DTOs\Customers\CustomerFilterData;
use App\Enums\Identity\DeletionRequestStatus;
use App\Enums\Identity\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Support\Auth\PhoneNumber;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<User>
 */
final class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(CustomerFilterData $filters): Builder
    {
        return $this->query()
            ->withTrashed()
            ->whereHas('customerProfile')
            ->with('customerProfile')
            ->withExists(['deletionRequests as has_pending_deletion' => static fn (Builder $query) => $query->where('status', DeletionRequestStatus::Pending->value)])
            ->when($filters->search, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn(
                'status',
                array_map(static fn (UserStatus $status): string => $status->value, $filters->statuses),
            ))
            ->when($filters->registeredFrom, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->registeredTo, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()))
            ->when($filters->cityIds !== [], static fn (Builder $query) => $query->whereExists(static fn ($sub) => $sub
                ->select(DB::raw(1))
                ->from('saved_places')
                ->join('service_zones', static fn ($join) => $join->whereRaw('ST_Contains(service_zones.polygon, saved_places.point)'))
                ->whereColumn('saved_places.user_id', 'users.id')
                ->whereIn('service_zones.city_id', $filters->cityIds)))
            ->when($filters->deletionRequested !== null, static fn (Builder $query) => $filters->deletionRequested
                ? $query->whereHas('deletionRequests', static fn (Builder $inner) => $inner->where('status', DeletionRequestStatus::Pending->value))
                : $query->whereDoesntHave('deletionRequests', static fn (Builder $inner) => $inner->where('status', DeletionRequestStatus::Pending->value)));
    }

    public function findByUuidWithTrashed(string $uuid): ?User
    {
        return $this->query()->withTrashed()->where('uuid', $uuid)->first();
    }

    public function lockForUpdate(int $id): ?User
    {
        return $this->query()->withTrashed()->whereKey($id)->lockForUpdate()->first();
    }

    public function suspensionsEndedBefore(CarbonInterface $now, int $limit): Collection
    {
        return $this->query()
            ->where('status', UserStatus::Suspended->value)
            ->whereNotNull('status_expires_at')
            ->where('status_expires_at', '<=', $now)
            ->orderBy('status_expires_at')
            ->limit($limit)
            ->get();
    }

    public function applyStatus(User $user, UserStatus $status, ?string $reason, ?CarbonInterface $expiresAt): User
    {
        $plain = in_array($status, [UserStatus::Active, UserStatus::Deleted], true);

        $user->forceFill([
            'status' => $status,
            'status_reason' => $plain ? null : $reason,
            'status_changed_at' => now(),
            'status_expires_at' => $plain ? null : $expiresAt,
        ])->save();

        return $user;
    }

    public function anonymise(User $user): User
    {
        $user->forceFill([
            'name' => null,
            'email' => null,
            'phone_e164' => null,
            'phone_verified_at' => null,
            'photo_path' => null,
            'status' => UserStatus::Deleted,
            'status_reason' => null,
            'status_changed_at' => now(),
            'status_expires_at' => null,
            'anonymised_at' => now(),
        ])->save();
        $user->delete();

        return $user;
    }

    public function hasProviderProfile(User $user): bool
    {
        return $user->providerProfile()->exists();
    }

    /**
     * One search box for uuid, phone and name: a uuid matches exactly, a full international number matches exactly,
     * a run of at least four digits matches the END of the number (the mask already shows the last four digits, so
     * this reveals nothing more), anything else is a name search.
     *
     * @param Builder<User> $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        if (preg_match(self::UUID_PATTERN, $search) === 1) {
            $query->where('uuid', strtolower($search));

            return;
        }

        $phone = PhoneNumber::normalize($search);

        if ($phone !== null) {
            $query->where('phone_e164', $phone);

            return;
        }

        $latin = strtr($search, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);

        if (preg_match('/^[0-9\s+\-().]{4,}$/', $latin) === 1 && strlen((string) preg_replace('/\D+/', '', $latin)) >= 4) {
            $query->where('phone_e164', 'like', '%'.preg_replace('/\D+/', '', $latin));

            return;
        }

        $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
    }
}
