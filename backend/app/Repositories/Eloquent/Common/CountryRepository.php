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

    public function lookup(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->query()
            ->when($term !== '', static fn ($query) => $query->where(static fn ($inner) => $inner
                ->where('iso2', 'like', $like)
                ->orWhere('name->ar', 'like', $like)
                ->orWhere('name->en', 'like', $like)))
            ->orderBy('iso2')
            ->limit($limit)
            ->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()->whereKey($ids)->orderBy('iso2')->get();
    }
}
