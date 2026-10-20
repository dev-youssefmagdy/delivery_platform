<?php

namespace App\Repositories\Contracts\Geography;

use App\DTOs\Geography\CityFilterData;
use App\Models\City;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<City>
 */
interface CityRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<City>
     */
    public function datatableQuery(CityFilterData $filters): Builder;

    /**
     * @return Collection<int, City>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, City>
     */
    public function findManyByIds(array $ids): Collection;

    public function zoneCount(City $city): int;

    public function activeZoneCount(City $city): int;

    /**
     * Cities of the active countries with their center, for map pickers.
     *
     * @return Collection<int, City>
     */
    public function activeList(): Collection;
}
