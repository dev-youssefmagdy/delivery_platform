<?php

namespace App\Repositories\Eloquent\Common;

use App\Models\Currency;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
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
}
