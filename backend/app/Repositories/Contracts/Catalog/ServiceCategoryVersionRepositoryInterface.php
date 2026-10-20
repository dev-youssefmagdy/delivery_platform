<?php

namespace App\Repositories\Contracts\Catalog;

use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ServiceCategoryVersion>
 */
interface ServiceCategoryVersionRepositoryInterface extends BaseRepositoryInterface
{
    public function nextVersionNumber(ServiceCategory $category): int;

    /**
     * Newest first, with the publisher for the history tab.
     *
     * @return Collection<int, ServiceCategoryVersion>
     */
    public function historyFor(ServiceCategory $category): Collection;

    public function countFor(ServiceCategory $category): int;
}
