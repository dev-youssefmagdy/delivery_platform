<?php

namespace App\Services\Catalog;

use App\Enums\Catalog\BookingType;
use App\Models\ServiceCategory;
use App\Models\ServiceFamily;
use App\Models\VehicleCategory;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;
use App\Repositories\Contracts\Catalog\VehicleCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read side of the catalog for the public API: only active entries with a published version are exposed.
 */
final class CatalogReadService
{
    public function __construct(
        private readonly ServiceFamilyRepositoryInterface $families,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly VehicleCategoryRepositoryInterface $vehicleCategories,
    ) {}

    /**
     * @return Collection<int, ServiceFamily>
     */
    public function families(): Collection
    {
        return $this->families->publicList();
    }

    /**
     * Zone availability (lat/lng) is wired in P1.5; until then every active, published category is available.
     *
     * @return Collection<int, ServiceCategory>
     */
    public function categories(?BookingType $bookingType, ?int $familyId): Collection
    {
        return $this->categories->publicList($bookingType, $familyId);
    }

    /**
     * @return Collection<int, VehicleCategory>
     */
    public function vehicleCategories(?int $serviceCategoryId): Collection
    {
        return $this->vehicleCategories->publicList($serviceCategoryId);
    }
}
