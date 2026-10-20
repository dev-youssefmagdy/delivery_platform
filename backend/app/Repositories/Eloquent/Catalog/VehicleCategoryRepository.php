<?php

namespace App\Repositories\Eloquent\Catalog;

use App\DTOs\Catalog\VehicleCategoryFilterData;
use App\Models\VehicleCategory;
use App\Repositories\Contracts\Catalog\VehicleCategoryRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<VehicleCategory>
 */
final class VehicleCategoryRepository extends BaseRepository implements VehicleCategoryRepositoryInterface
{
    public function __construct(VehicleCategory $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(VehicleCategoryFilterData $filters): Builder
    {
        return $this->query()
            ->withCount('serviceCategories')
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('code', 'like', $like)
                    ->orWhere('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like));
            })
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

    public function serviceCategoryCount(VehicleCategory $vehicleCategory): int
    {
        return $vehicleCategory->serviceCategories()->count();
    }

    public function publicList(?int $serviceCategoryId): Collection
    {
        return $this->query()
            ->where('active', true)
            ->when($serviceCategoryId !== null, static fn (Builder $query) => $query->whereHas(
                'serviceCategories',
                static fn (Builder $inner) => $inner->whereKey($serviceCategoryId),
            ))
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }
}
