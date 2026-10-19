<?php

namespace App\Repositories\Contracts\Common;

use App\Models\Country;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Country>
 */
interface CountryRepositoryInterface extends BaseRepositoryInterface
{
    public function findByIso2(string $iso2): ?Country;

    /**
     * @return Collection<int, Country>
     */
    public function active(): Collection;

    /**
     * Remote-select search by ISO code or name (any locale).
     *
     * @return Collection<int, Country>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, Country>
     */
    public function findManyByIds(array $ids): Collection;
}
