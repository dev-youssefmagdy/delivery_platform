<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Enums\Content\ContentWorkflowStatus;
use App\Enums\Content\MenuLocation;
use App\Models\ContentMenu;
use App\Repositories\Contracts\Content\MenuRepositoryInterface;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends ContentRepository<ContentMenu>
 */
final class MenuRepository extends ContentRepository implements MenuRepositoryInterface
{
    public function __construct(ContentMenu $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ContentFilterData $filters): Builder
    {
        return $this->applyCommonFilters($this->query(), $filters)
            ->when($filters->locations !== [], static fn (Builder $query) => $query->whereIn('location', array_map(static fn (BackedEnum $location): string => $location->value, $filters->locations)));
    }

    protected function searchColumns(): array
    {
        return ['title'];
    }

    protected function completenessColumn(): string
    {
        return 'title';
    }

    public function lockPublishedAt(MenuLocation $location, int $exceptId): Collection
    {
        return $this->query()
            ->where('location', $location->value)
            ->where('status', ContentWorkflowStatus::Published->value)
            ->whereKeyNot($exceptId)
            ->lockForUpdate()
            ->get();
    }
}
