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
}
