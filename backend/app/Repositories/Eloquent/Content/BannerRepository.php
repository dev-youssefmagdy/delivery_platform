<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Models\ContentBanner;
use App\Repositories\Contracts\Content\BannerRepositoryInterface;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends ContentRepository<ContentBanner>
 */
final class BannerRepository extends ContentRepository implements BannerRepositoryInterface
{
    public function __construct(ContentBanner $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ContentFilterData $filters): Builder
    {
        return $this->applyCommonFilters($this->query(), $filters)
            ->when($filters->placements !== [], static fn (Builder $query) => $query->whereIn('placement', array_map(static fn (BackedEnum $placement): string => $placement->value, $filters->placements)));
    }

    protected function searchColumns(): array
    {
        return ['title'];
    }

    protected function completenessColumn(): string
    {
        return 'title';
    }
}
