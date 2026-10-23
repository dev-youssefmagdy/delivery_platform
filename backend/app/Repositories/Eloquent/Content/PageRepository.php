<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Models\ContentPage;
use App\Repositories\Contracts\Content\PageRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends ContentRepository<ContentPage>
 */
final class PageRepository extends ContentRepository implements PageRepositoryInterface
{
    public function __construct(ContentPage $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ContentFilterData $filters): Builder
    {
        return $this->applyCommonFilters($this->query(), $filters)
            ->when($filters->pageKinds !== [], static fn (Builder $query) => $query->whereIn('kind', array_map(static fn (\BackedEnum $kind): string => $kind->value, $filters->pageKinds)));
    }

    protected function searchColumns(): array
    {
        return ['title', 'summary'];
    }

    protected function completenessColumn(): string
    {
        return 'title';
    }
}
