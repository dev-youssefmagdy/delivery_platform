<?php

namespace App\Services\Catalog;

use App\DTOs\Catalog\CategoryListing;
use App\DTOs\Geography\CategoryAvailability;
use App\Enums\Catalog\BookingType;
use App\Models\ServiceCategory;
use App\Models\ServiceFamily;
use App\Models\VehicleCategory;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;
use App\Repositories\Contracts\Catalog\VehicleCategoryRepositoryInterface;
use App\Services\Geography\GeoService;
use App\Support\Geo\GeoPoint;
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
        private readonly GeoService $geo,
    ) {}

    /**
     * @return Collection<int, ServiceFamily>
     */
    public function families(): Collection
    {
        return $this->families->publicList();
    }

    /**
     * Without a position every active, published category is listed (catalog browsing). With one, only the
     * categories the serving zone offers right now are returned (zone matrix, zone/category/override hours) and
     * each carries the zone's overrides as `zone_availability`; outside every active zone the list is empty.
     */
    public function categories(?BookingType $bookingType, ?int $familyId, ?GeoPoint $at = null): CategoryListing
    {
        if ($at === null) {
            return new CategoryListing($this->categories->publicList($bookingType, $familyId), null);
        }

        $area = $this->geo->resolve($at);

        if ($area->zone === null) {
            return new CategoryListing(new Collection, $area);
        }

        $zoneId = $area->zone->id;
        $open = collect($area->availableCategories())->keyBy(static fn (CategoryAvailability $a): int => $a->category->id);
        $categories = $this->categories->publicList($bookingType, $familyId, $zoneId)
            ->filter(static fn (ServiceCategory $category): bool => $open->has($category->id))
            ->each(static function (ServiceCategory $category) use ($open, $zoneId): void {
                $category->setAttribute('zone_availability', ['zone_id' => $zoneId, 'overrides' => $open[$category->id]->overrides]);
            })
            ->values();

        return new CategoryListing($categories, $area);
    }

    /**
     * @return Collection<int, VehicleCategory>
     */
    public function vehicleCategories(?int $serviceCategoryId): Collection
    {
        return $this->vehicleCategories->publicList($serviceCategoryId);
    }
}
