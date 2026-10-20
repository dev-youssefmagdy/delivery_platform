<?php

namespace App\Repositories\Eloquent\Catalog;

use App\DTOs\Catalog\ServiceFamilyFilterData;
use App\Models\ServiceFamily;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<ServiceFamily>
 */
final class ServiceFamilyRepository extends BaseRepository implements ServiceFamilyRepositoryInterface
{
    public function __construct(ServiceFamily $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(ServiceFamilyFilterData $filters): Builder
    {
        return $this->query()
            ->withCount('categories')
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('code', 'like', $like)
                    ->orWhere('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like));
            })
            ->when($filters->kinds !== [], static fn (Builder $query) => $query->whereIn('kind', array_map(static fn ($kind): string => $kind->value, $filters->kinds)))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active));
    }

    public function lookup(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->query()
            ->when($term !== '', static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('code', 'like', $like)
                ->orWhere('name->ar', 'like', $like)
                ->orWhere('name->en', 'like', $like)))
            ->orderBy('sort_order')->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()->whereKey($ids)->orderBy('sort_order')->get();
    }

    public function allOrdered(): Collection
    {
        return $this->query()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('code', $code)
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function categoryCount(ServiceFamily $family): int
    {
        return $family->categories()->count();
    }

    public function activeCategoryCount(ServiceFamily $family): int
    {
        return $family->categories()->where('active', true)->count();
    }

    public function publicList(): Collection
    {
        return $this->query()
            ->where('active', true)
            ->whereHas('categories', static fn (Builder $query) => $query->where('active', true)->whereNotNull('published_version_id'))
            ->withCount(['categories as public_categories_count' => static fn (Builder $query) => $query->where('active', true)->whereNotNull('published_version_id')])
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }
}
