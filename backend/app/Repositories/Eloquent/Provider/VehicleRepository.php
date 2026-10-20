<?php

namespace App\Repositories\Eloquent\Provider;

use App\DTOs\Provider\VehicleFilterData;
use App\Enums\Provider\VehicleStatus;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Vehicle>
 */
final class VehicleRepository extends BaseRepository implements VehicleRepositoryInterface
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(VehicleFilterData $filters): Builder
    {
        return $this->query()
            ->with(['vehicleCategory', 'providerProfile.user' => static fn ($query) => $query->withTrashed()->select(['id', 'uuid', 'name'])])
            ->when($filters->plate, static fn (Builder $query, string $plate) => $query->where('plate_normalized', 'like', '%'.addcslashes(self::normalizePlateSearch($plate), '%_\\').'%'))
            ->when($filters->vehicleCategoryIds !== [], static fn (Builder $query) => $query->whereIn('vehicle_category_id', $filters->vehicleCategoryIds))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('status', array_map(static fn (VehicleStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->providerIds !== [], static fn (Builder $query) => $query->whereIn('provider_profile_id', $filters->providerIds));
    }

    public function providerTabQuery(ProviderProfile $profile): Builder
    {
        return $this->query()->with('vehicleCategory')->where('provider_profile_id', $profile->id);
    }

    public function forProvider(ProviderProfile $profile): Collection
    {
        return $this->query()->with(['vehicleCategory', 'photos'])->where('provider_profile_id', $profile->id)->orderBy('id')->get();
    }

    public function findByUuidWithRelations(string $uuid): ?Vehicle
    {
        return $this->query()
            ->with(['vehicleCategory', 'photos', 'providerProfile.user' => static fn ($query) => $query->withTrashed(), 'providerProfile.city'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function lockForUpdate(int $id): ?Vehicle
    {
        return $this->query()->whereKey($id)->lockForUpdate()->first();
    }

    public function plateExists(string $normalizedPlate, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('plate_normalized', $normalizedPlate)
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function countForProvider(ProviderProfile $profile): int
    {
        return $this->query()->where('provider_profile_id', $profile->id)->count();
    }

    public function applyStatus(Vehicle $vehicle, VehicleStatus $status, ?int $reviewerId, ?string $reason): Vehicle
    {
        $attributes = [
            'status' => $status,
            'status_changed_at' => now(),
            'rejection_reason' => $status === VehicleStatus::Rejected ? $reason : null,
            'lock_version' => $vehicle->lock_version + 1,
        ];

        if ($status === VehicleStatus::UnderReview) {
            $attributes['submitted_at'] = now();
        }

        if (in_array($status, [VehicleStatus::Approved, VehicleStatus::Rejected], true)) {
            $attributes['reviewed_by_admin_id'] = $reviewerId;
            $attributes['reviewed_at'] = now();
        }

        $vehicle->forceFill($attributes)->save();

        return $vehicle;
    }

    private static function normalizePlateSearch(string $plate): string
    {
        return strtoupper((string) preg_replace('/[\s\-_.]+/u', '', strtr($plate, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'])));
    }
}
