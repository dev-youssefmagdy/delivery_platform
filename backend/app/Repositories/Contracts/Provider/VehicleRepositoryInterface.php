<?php

namespace App\Repositories\Contracts\Provider;

use App\DTOs\Provider\VehicleFilterData;
use App\Enums\Provider\VehicleStatus;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Vehicle>
 */
interface VehicleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<Vehicle>
     */
    public function datatableQuery(VehicleFilterData $filters): Builder;

    /**
     * @return Builder<Vehicle>
     */
    public function providerTabQuery(ProviderProfile $profile): Builder;

    /**
     * @return Collection<int, Vehicle> with category and photos
     */
    public function forProvider(ProviderProfile $profile): Collection;

    public function findByUuidWithRelations(string $uuid): ?Vehicle;

    public function lockForUpdate(int $id): ?Vehicle;

    public function plateExists(string $normalizedPlate, ?int $exceptId = null): bool;

    public function countForProvider(ProviderProfile $profile): int;

    /**
     * Writes the status, review markers and rejection reason.
     */
    public function applyStatus(Vehicle $vehicle, VehicleStatus $status, ?int $reviewerId, ?string $reason): Vehicle;
}
