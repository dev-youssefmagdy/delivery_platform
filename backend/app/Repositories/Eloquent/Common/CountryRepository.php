<?php

namespace App\Repositories\Eloquent\Common;

use App\Models\Country;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Country>
 */
final class CountryRepository extends BaseRepository implements CountryRepositoryInterface
{
    public function __construct(Country $model)
    {
        parent::__construct($model);
    }

    public function findByIso2(string $iso2): ?Country
    {
        return $this->query()->where('iso2', strtoupper($iso2))->first();
    }

    public function active(): Collection
    {
        return $this->query()->where('active', true)->orderBy('iso2')->get();
    }
}
