<?php

namespace App\Repositories\Contracts\Demo;

use App\DTOs\Demo\DemoItemFilterData;
use App\Models\DemoItem;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepositoryInterface<DemoItem>
 */
interface DemoItemRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Listing query (DataTable + CSV export) with the filters card applied.
     *
     * @return Builder<DemoItem>
     */
    public function datatableQuery(DemoItemFilterData $filters): Builder;

    /**
     * @return array<string, int> status value => count
     */
    public function countByStatus(): array;

    /**
     * Loads what the detail/edit screens display (avoids lazy loading).
     */
    public function loadDisplayRelations(DemoItem $demoItem): DemoItem;
}
