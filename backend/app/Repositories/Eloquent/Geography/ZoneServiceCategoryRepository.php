<?php

namespace App\Repositories\Eloquent\Geography;

use App\Models\ServiceZone;
use App\Models\ZoneServiceCategory;
use App\Repositories\Contracts\Geography\ZoneServiceCategoryRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ZoneServiceCategory>
 */
final class ZoneServiceCategoryRepository extends BaseRepository implements ZoneServiceCategoryRepositoryInterface
{
    public function __construct(ZoneServiceCategory $model)
    {
        parent::__construct($model);
    }

    public function forZone(int $zoneId): Collection
    {
        return $this->query()
            ->where('service_zone_id', $zoneId)
            ->with(['category.publishedVersion'])
            ->get()
            ->keyBy('service_category_id');
    }

    public function enabledCount(int $zoneId): int
    {
        return $this->query()->where('service_zone_id', $zoneId)->where('active', true)->count();
    }

    public function replaceForZone(ServiceZone $zone, array $rows): void
    {
        $keep = [];

        foreach ($rows as $row) {
            $keep[] = $row->serviceCategoryId;
            $this->query()->updateOrCreate(
                ['service_zone_id' => $zone->getKey(), 'service_category_id' => $row->serviceCategoryId],
                ['active' => $row->active, 'overrides' => $row->overrides()],
            );
        }

        $this->query()->where('service_zone_id', $zone->getKey())->whereNotIn('service_category_id', $keep)->delete();
    }

    public function enableAll(ServiceZone $zone, array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            $this->query()->updateOrCreate(
                ['service_zone_id' => $zone->getKey(), 'service_category_id' => $categoryId],
                ['active' => true, 'overrides' => null],
            );
        }
    }
}
