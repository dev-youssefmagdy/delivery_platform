<?php

namespace App\Repositories\Contracts\Catalog;

use App\DTOs\Catalog\VehicleCategoryFilterData;
use App\Models\VehicleCategory;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<VehicleCategory>
 */
interface VehicleCategoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<VehicleCategory>
     */
    public function datatableQuery(VehicleCategoryFilterData $filters): Builder;

    /**
     * @return Collection<int, VehicleCategory>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, VehicleCategory>
     */
    public function findManyByIds(array $ids): Collection;

    /**
     * @return Collection<int, VehicleCategory>
     */
    public function allOrdered(): Collection;

    public function codeExists(string $code, ?int $exceptId = null): bool;

    public function serviceCategoryCount(VehicleCategory $vehicleCategory): int;

    /**
     * Active vehicle categories for the public API, optionally limited to a service category's links.
     *
     * @return Collection<int, VehicleCategory>
     */
    public function publicList(?int $serviceCategoryId): Collection;
}
