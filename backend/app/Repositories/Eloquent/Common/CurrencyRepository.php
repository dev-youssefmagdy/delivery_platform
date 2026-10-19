<?php

namespace App\Repositories\Eloquent\Common;

use App\DTOs\Markets\CurrencyFilterData;
use App\Models\Currency;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Currency>
 */
final class CurrencyRepository extends BaseRepository implements CurrencyRepositoryInterface
{
    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->query()->where('code', strtoupper($code))->first();
    }

    public function active(): Collection
    {
        return $this->query()->where('active', true)->orderBy('code')->get();
    }

    public function datatableQuery(CurrencyFilterData $filters): Builder
    {
        return $this->query()
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('code', 'like', $like)
                    ->orWhere('symbol_ar', 'like', $like)
                    ->orWhere('symbol_en', 'like', $like));
            })
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active))
            ->when($filters->createdFrom, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->createdTo, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('code', strtoupper($code))
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
