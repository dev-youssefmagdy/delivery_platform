<?php

namespace App\Repositories\Contracts\Common;

use App\DTOs\Markets\CurrencyFilterData;
use App\Models\Currency;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Listing query (DataTable) with the filters card applied.
     *
     * @return Builder<Currency>
     */
    public function datatableQuery(CurrencyFilterData $filters): Builder;

    public function codeExists(string $code, ?int $exceptId = null): bool;
}
