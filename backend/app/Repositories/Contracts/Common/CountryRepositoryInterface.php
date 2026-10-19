<?php

namespace App\Repositories\Contracts\Common;

use App\DTOs\Markets\CountryFilterData;
use App\Models\Country;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Listing query (DataTable) with the filters card applied.
     *
     * @return Builder<Country>
     */
    public function datatableQuery(CountryFilterData $filters): Builder;

    public function isoExists(string $iso2, ?int $exceptId = null): bool;

    public function countUsingCurrency(string $currencyCode): int;
}
