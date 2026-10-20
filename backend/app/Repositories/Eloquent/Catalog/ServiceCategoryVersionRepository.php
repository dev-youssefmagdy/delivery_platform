<?php

namespace App\Repositories\Eloquent\Catalog;

use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Repositories\Contracts\Catalog\ServiceCategoryVersionRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ServiceCategoryVersion>
 */
final class ServiceCategoryVersionRepository extends BaseRepository implements ServiceCategoryVersionRepositoryInterface
{
    public function __construct(ServiceCategoryVersion $model)
    {
        parent::__construct($model);
    }

    public function nextVersionNumber(ServiceCategory $category): int
    {
        return ((int) $this->query()->where('service_category_id', $category->getKey())->max('version')) + 1;
    }

    public function historyFor(ServiceCategory $category): Collection
    {
        return $this->query()
            ->where('service_category_id', $category->getKey())
            ->with('publisher:id,name')
            ->orderByDesc('version')
            ->get();
    }

    public function countFor(ServiceCategory $category): int
    {
        return $this->query()->where('service_category_id', $category->getKey())->count();
    }
}
