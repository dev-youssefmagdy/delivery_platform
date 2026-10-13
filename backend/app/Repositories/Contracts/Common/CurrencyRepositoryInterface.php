<?php

namespace App\Repositories\Contracts\Common;

use App\Models\Currency;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Currency>
 */
interface CurrencyRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code): ?Currency;

    /**
     * @return Collection<int, Currency>
     */
    public function active(): Collection;
}
