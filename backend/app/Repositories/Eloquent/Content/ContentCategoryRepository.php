<?php

namespace App\Repositories\Eloquent\Content;

use App\DTOs\Content\ContentFilterData;
use App\Enums\Content\CategoryKind;
use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\ContentFaq;
use App\Repositories\Contracts\Content\ContentCategoryRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ContentCategory>
 */
final class ContentCategoryRepository extends BaseRepository implements ContentCategoryRepositoryInterface
{
    public function __construct(ContentCategory $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ContentFilterData $filters): Builder
    {
        return $this->query()
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like)
                    ->orWhere('slug', 'like', $like));
            })
            ->when($filters->categoryKind, static fn (Builder $query, CategoryKind $kind) => $query->where('kind', $kind->value))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active));
    }

    public function options(CategoryKind $kind): array
    {
        return $this->query()
            ->where('kind', $kind->value)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(static fn (ContentCategory $category): array => [$category->id => (string) $category->getTranslation('name', app()->getLocale(), true)])
            ->all();
    }

    public function usageCount(ContentCategory $category): int
    {
        return $category->kind === CategoryKind::Faq
            ? ContentFaq::query()->where('category_id', $category->id)->count()
            : ContentArticle::query()->where('category_id', $category->id)->count();
    }

    public function lookup(CategoryKind $kind, string $term, int $limit): Collection
    {
        return $this->query()
            ->where('kind', $kind->value)
            ->where('active', true)
            ->when($term !== '', static function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner->where('name->ar', 'like', $like)->orWhere('name->en', 'like', $like)->orWhere('slug', 'like', $like));
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(CategoryKind $kind, array $ids): Collection
    {
        return $this->query()->where('kind', $kind->value)->whereIn('id', $ids)->get();
    }
}
