<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Models\ContentArticle;
use App\Repositories\Contracts\Content\ArticleRepositoryInterface;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends ContentRepository<ContentArticle>
 */
final class ArticleRepository extends ContentRepository implements ArticleRepositoryInterface
{
    public function __construct(ContentArticle $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ContentFilterData $filters): Builder
    {
        return $this->applyCommonFilters($this->query(), $filters)
            ->when($filters->articleTypes !== [], static fn (Builder $query) => $query->whereIn('type', array_map(static fn (BackedEnum $type): string => $type->value, $filters->articleTypes)))
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereIn('category_id', $filters->categoryIds));
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
