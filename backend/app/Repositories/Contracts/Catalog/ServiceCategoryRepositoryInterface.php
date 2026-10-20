<?php

namespace App\Repositories\Contracts\Catalog;

use App\DTOs\Catalog\ServiceCategoryFilterData;
use App\Enums\Catalog\BookingType;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ServiceCategory>
 */
interface ServiceCategoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<ServiceCategory>
     */
    public function datatableQuery(ServiceCategoryFilterData $filters): Builder;

    /**
     * @return Collection<int, ServiceCategory>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, ServiceCategory>
     */
    public function findManyByIds(array $ids): Collection;

    public function codeExists(string $code, ?int $exceptId = null): bool;

    /**
     * Number of active categories, optionally ignoring one (used by the "last active category" guard).
     */
    public function activeCount(?int $exceptId = null, ?int $exceptFamilyId = null): int;

    /**
     * Replaces the linked vehicle categories; returns the ids that were attached / detached.
     *
     * @param list<int> $vehicleCategoryIds
     * @return array{attached: list<int>, detached: list<int>}
     */
    public function syncVehicleCategories(ServiceCategory $category, array $vehicleCategoryIds): array;

    /**
     * @return list<int>
     */
    public function vehicleCategoryIds(ServiceCategory $category): array;

    /**
     * Details for the admin screens (family, vehicle categories, published version).
     */
    public function loadDetails(ServiceCategory $category): ServiceCategory;

    /**
     * Every category (any status) in display order, for the zone availability matrix.
     *
     * @return Collection<int, ServiceCategory>
     */
    public function allOrdered(): Collection;

    /**
     * Categories shown to clients: active, in an active family, with a published version. With `$zoneId` only the
     * categories enabled in that zone's availability matrix are returned (P1.5); opening hours are applied by the caller.
     *
     * @return Collection<int, ServiceCategory>
     */
    public function publicList(?BookingType $bookingType, ?int $familyId, ?int $zoneId = null): Collection;
}
