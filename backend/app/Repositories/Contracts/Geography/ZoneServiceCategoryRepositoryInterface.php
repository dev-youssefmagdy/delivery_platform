<?php

namespace App\Repositories\Contracts\Geography;

use App\DTOs\Geography\ZoneCategoryAvailabilityData;
use App\Models\ServiceZone;
use App\Models\ZoneServiceCategory;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ZoneServiceCategory>
 */
interface ZoneServiceCategoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Every link of the zone with its category (and published version) loaded, keyed by service_category_id.
     *
     * @return Collection<int, ZoneServiceCategory>
     */
    public function forZone(int $zoneId): Collection;

    public function enabledCount(int $zoneId): int;

    /**
     * Makes the zone's links equal to `$rows` (upsert present, delete absent).
     *
     * @param list<ZoneCategoryAvailabilityData> $rows
     */
    public function replaceForZone(ServiceZone $zone, array $rows): void;

    /**
     * Enable every given category in the zone without overrides (seeders, tests).
     *
     * @param list<int> $categoryIds
     */
    public function enableAll(ServiceZone $zone, array $categoryIds): void;
}
