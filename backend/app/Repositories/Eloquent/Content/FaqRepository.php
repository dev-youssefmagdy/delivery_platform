<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Models\ContentFaq;
use App\Repositories\Contracts\Content\FaqRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends ContentRepository<ContentFaq>
 */
final class FaqRepository extends ContentRepository implements FaqRepositoryInterface
{
    public function __construct(ContentFaq $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ContentFilterData $filters): Builder
    {
        return $this->applyCommonFilters($this->query(), $filters)
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereIn('category_id', $filters->categoryIds));
    }

    protected function searchColumns(): array
    {
        return ['question', 'answer'];
    }

    protected function completenessColumn(): string
    {
        return 'question';
    }
}
