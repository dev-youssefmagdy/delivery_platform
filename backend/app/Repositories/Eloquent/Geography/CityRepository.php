<?php

namespace App\Repositories\Eloquent\Geography;

use App\DTOs\Geography\CityFilterData;
use App\Enums\Geo\ZoneStatus;
use App\Models\City;
use App\Repositories\Contracts\Geography\CityRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<City>
 */
final class CityRepository extends BaseRepository implements CityRepositoryInterface
{
    public function __construct(City $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(CityFilterData $filters): Builder
    {
        return $this->query()
            ->with('country')
            ->withCount('zones')
            ->when($filters->search, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like));
            })
            ->when($filters->countryIds !== [], static fn (Builder $query) => $query->whereIn('country_id', $filters->countryIds))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active));
    }

    public function lookup(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->query()
            ->when($term !== '', static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('name->ar', 'like', $like)
                ->orWhere('name->en', 'like', $like)))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()->whereKey($ids)->orderBy('id')->get();
    }

    public function zoneCount(City $city): int
    {
        return $city->zones()->count();
    }

    public function activeZoneCount(City $city): int
    {
        return $city->zones()->where('status', ZoneStatus::Active->value)->count();
    }

    public function activeList(): Collection
    {
        return $this->query()->where('active', true)->orderBy('id')->get();
    }
}
