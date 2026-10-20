<?php

namespace Tests\Concerns;

use App\Models\City;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Models\VehicleCategory;
use App\Models\ZoneServiceCategory;
use App\Services\Catalog\ServiceCategoryPublisher;

/**
 * Fixtures for geography tests. The default zone is the square 30.0..30.1 N, 31.2..31.3 E (Cairo, Africa/Cairo).
 */
trait BuildsGeography
{
    /**
     * An active, published category that can be offered by zones.
     *
     * @param array<string, mixed> $attributes
     */
    protected function bookableCategory(array $attributes = []): ServiceCategory
    {
        $category = ServiceCategory::factory()->create($attributes);
        $category->vehicleCategories()->attach(VehicleCategory::factory()->create());
        app(ServiceCategoryPublisher::class)->publish($category, 'Release', null);
        $category->refresh()->forceFill(['active' => true])->save();

        return $category;
    }

    /**
     * @param list<ServiceCategory> $categories
     * @param array<string, mixed> $zoneAttributes
     */
    protected function zoneOffering(array $categories, array $zoneAttributes = [], ?City $city = null): ServiceZone
    {
        $zone = ServiceZone::factory()->create($zoneAttributes + ['city_id' => $city ?? City::factory()]);

        foreach ($categories as $category) {
            ZoneServiceCategory::query()->create(['service_zone_id' => $zone->id, 'service_category_id' => $category->id, 'active' => true]);
        }

        return $zone;
    }
}
