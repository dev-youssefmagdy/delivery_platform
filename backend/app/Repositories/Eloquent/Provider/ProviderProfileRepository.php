<?php

namespace App\Repositories\Eloquent\Provider;

use App\DTOs\Provider\ProviderFilterData;
use App\DTOs\Provider\VerificationQueueFilterData;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Repositories\Eloquent\Concerns\SearchesUsers;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<ProviderProfile>
 */
final class ProviderProfileRepository extends BaseRepository implements ProviderProfileRepositoryInterface
{
    use SearchesUsers;

    public function __construct(ProviderProfile $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ProviderFilterData $filters): Builder
    {
        return $this->query()
            ->with(['user' => static fn ($query) => $query->withTrashed()->select(['id', 'uuid', 'name', 'phone_e164', 'anonymised_at']), 'city'])
            ->withCount('vehicles')
            ->when($filters->search, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('status', array_map(static fn (ProviderStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->providerTypes !== [], static fn (Builder $query) => $query->whereIn('provider_type', array_map(static fn ($type): string => $type->value, $filters->providerTypes)))
            ->when($filters->cityIds !== [], static fn (Builder $query) => $query->whereIn('city_id', $filters->cityIds))
            ->when($filters->serviceCategoryIds !== [], static fn (Builder $query) => $query->whereHas('services', static fn (Builder $inner) => $inner
                ->whereIn('service_category_id', $filters->serviceCategoryIds)
                ->where('status', '!=', ServiceApprovalStatus::Rejected->value)))
            ->when($filters->online !== null, static fn (Builder $query) => $query->where('is_online', $filters->online))
            ->when($filters->documentExpiringWithinDays !== null, fn (Builder $query) => $this->applyExpiring($query, (int) $filters->documentExpiringWithinDays));
    }

    public function verificationQueueQuery(VerificationQueueFilterData $filters): Builder
    {
        return $this->query()
            ->with(['user' => static fn ($query) => $query->select(['id', 'uuid', 'name']), 'city', 'assignedAdmin:id,name'])
            ->whereIn('status', array_map(static fn (ProviderStatus $status): string => $status->value, $filters->effectiveStatuses()))
            ->when($filters->providerTypes !== [], static fn (Builder $query) => $query->whereIn('provider_type', array_map(static fn ($type): string => $type->value, $filters->providerTypes)))
            ->when($filters->cityIds !== [], static fn (Builder $query) => $query->whereIn('city_id', $filters->cityIds))
            ->when($filters->waitingMoreThanHours !== null, static fn (Builder $query) => $query->where('submitted_at', '<=', now()->subHours((int) $filters->waitingMoreThanHours)))
            ->when($filters->assignedAdminIds !== [], static fn (Builder $query) => $query->whereIn('assigned_admin_id', $filters->assignedAdminIds))
            ->when($filters->unassignedOnly === true, static fn (Builder $query) => $query->whereNull('assigned_admin_id'));
    }

    public function findByUserId(int $userId): ?ProviderProfile
    {
        return $this->query()->where('user_id', $userId)->first();
    }

    public function findByUuidWithRelations(string $uuid): ?ProviderProfile
    {
        return $this->query()
            ->with(['user' => static fn ($query) => $query->withTrashed(), 'city.country', 'assignedAdmin:id,name'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function lockForUpdate(int $id): ?ProviderProfile
    {
        return $this->query()->whereKey($id)->lockForUpdate()->first();
    }

    public function createFor(User $user): ProviderProfile
    {
        $profile = new ProviderProfile(['user_id' => $user->id]);
        $profile->save();

        return $profile;
    }

    public function updateApplication(ProviderProfile $profile, array $attributes): ProviderProfile
    {
        $profile->fill($attributes);
        $profile->forceFill(['lock_version' => $profile->lock_version + 1])->save();

        return $profile;
    }

    public function applyStatus(ProviderProfile $profile, ProviderStatus $status, ?string $reason, ?CarbonInterface $expiresAt): ProviderProfile
    {
        $attributes = [
            'status' => $status,
            'status_reason' => $reason,
            'status_changed_at' => now(),
            'status_expires_at' => $status === ProviderStatus::TemporarilySuspended ? $expiresAt : null,
            'lock_version' => $profile->lock_version + 1,
        ];

        if ($status === ProviderStatus::UnderReview) {
            $attributes['submitted_at'] = now();
            $attributes['assigned_admin_id'] = null;
        }

        if ($status === ProviderStatus::Approved && $profile->approved_at === null) {
            $attributes['approved_at'] = now();
        }

        if ($status->isSuspended() || $status === ProviderStatus::DeletionPending) {
            $attributes['is_online'] = false;
            $attributes['online_since'] = null;
        }

        $profile->forceFill($attributes)->save();

        return $profile;
    }

    public function setOnline(ProviderProfile $profile, bool $online): ProviderProfile
    {
        $profile->forceFill(['is_online' => $online, 'online_since' => $online ? now() : null])->save();

        return $profile;
    }

    public function setCurrentVehicle(ProviderProfile $profile, ?int $vehicleId): ProviderProfile
    {
        $profile->forceFill(['current_vehicle_id' => $vehicleId])->save();

        return $profile;
    }

    public function assignTo(ProviderProfile $profile, ?int $adminId): ProviderProfile
    {
        $profile->forceFill(['assigned_admin_id' => $adminId])->save();

        return $profile;
    }

    public function scrubPersonalData(ProviderProfile $profile): ProviderProfile
    {
        $profile->forceFill([
            'date_of_birth' => null,
            'city_id' => null,
            'is_online' => false,
            'online_since' => null,
            'current_vehicle_id' => null,
            'assigned_admin_id' => null,
        ])->save();

        return $profile;
    }

    public function suspensionsEndedBefore(CarbonInterface $now, int $limit): Collection
    {
        return $this->query()
            ->where('status', ProviderStatus::TemporarilySuspended->value)
            ->whereNotNull('status_expires_at')
            ->where('status_expires_at', '<=', $now)
            ->orderBy('status_expires_at')
            ->limit($limit)
            ->get();
    }

    public function lookup(string $term, int $limit): Collection
    {
        return $this->query()
            ->with(['user' => static fn ($query) => $query->withTrashed()->select(['id', 'uuid', 'name'])])
            ->when($term !== '', fn (Builder $query) => $this->applySearch($query, $term))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()
            ->with(['user' => static fn ($query) => $query->withTrashed()->select(['id', 'uuid', 'name'])])
            ->whereKey($ids)
            ->get();
    }

    /**
     * Uuid (provider or account), phone and name search, delegated to the shared users search.
     *
     * @param Builder<ProviderProfile> $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $inner) use ($search): void {
            if (self::looksLikeUuid($search)) {
                $inner->where('uuid', strtolower($search));
            }

            $inner->orWhereHas('user', fn (Builder $users) => $this->applyUserSearch($users->withTrashed(), $search));
        });
    }

    /**
     * Providers with an approved document of their own or of one of their vehicles that expires within N days.
     *
     * @param Builder<ProviderProfile> $query
     */
    private function applyExpiring(Builder $query, int $days): void
    {
        $from = now()->startOfDay()->toDateString();
        $until = now()->addDays($days)->toDateString();

        $query->where(static function (Builder $inner) use ($from, $until): void {
            $inner->whereExists(static fn ($sub) => $sub->select(DB::raw(1))->from('provider_documents')
                ->whereColumn('provider_documents.provider_profile_id', 'provider_profiles.id')
                ->whereNull('provider_documents.deleted_at')
                ->where('provider_documents.status', DocumentStatus::Approved->value)
                ->whereBetween('provider_documents.expires_at', [$from, $until]))
                ->orWhereExists(static fn ($sub) => $sub->select(DB::raw(1))->from('vehicle_documents')
                    ->join('vehicles', 'vehicles.id', '=', 'vehicle_documents.vehicle_id')
                    ->whereColumn('vehicles.provider_profile_id', 'provider_profiles.id')
                    ->whereNull('vehicle_documents.deleted_at')
                    ->where('vehicle_documents.status', DocumentStatus::Approved->value)
                    ->whereBetween('vehicle_documents.expires_at', [$from, $until]));
        });
    }
}
