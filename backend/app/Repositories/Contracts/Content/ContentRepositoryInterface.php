<?php

namespace App\Repositories\Contracts\Content;

use App\DTOs\Content\ContentFilterData;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Repository of a CMS entity that goes through the publishing workflow.
 *
 * @template TModel of Model
 *
 * @extends BaseRepositoryInterface<TModel>
 */
interface ContentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Listing query (DataTable) with the filters card applied.
     *
     * @return Builder<TModel>
     */
    public function datatableQuery(ContentFilterData $filters): Builder;

    /**
     * Ids of scheduled items whose time has come (the scheduled-publish job locks and re-checks each one).
     *
     * @return list<int>
     */
    public function dueScheduledIds(CarbonInterface $now): array;

    /**
     * @param list<string> $relations
     * @return TModel
     */
    public function loadRelations(Model $model, array $relations): Model;
}
