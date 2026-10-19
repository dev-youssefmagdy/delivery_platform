<?php

namespace App\Repositories\Eloquent\Common;

use App\DTOs\Markets\CountryFilterData;
use App\Models\Country;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
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

    public function datatableQuery(CountryFilterData $filters): Builder
    {
        return $this->query()
            ->with('currency:code,symbol_ar,symbol_en')
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('iso2', 'like', $like)
                    ->orWhere('name->ar', 'like', $like)
                    ->orWhere('name->en', 'like', $like));
            })
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active))
            ->when($filters->currencyCode, static fn (Builder $query, string $code) => $query->where('currency_code', $code))
            ->when($filters->createdFrom, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->createdTo, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }

    public function isoExists(string $iso2, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('iso2', strtoupper($iso2))
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function countUsingCurrency(string $currencyCode): int
    {
        return $this->query()->where('currency_code', strtoupper($currencyCode))->count();
    }
}
