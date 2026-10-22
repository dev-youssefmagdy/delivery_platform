<?php

namespace App\Repositories\Contracts\Finance;

use App\DTOs\Finance\CashReconciliationFilterData;
use App\Models\CashReconciliation;
use Illuminate\Database\Eloquent\Builder;

interface CashReconciliationRepositoryInterface
{
    public function find(int $id): ?CashReconciliation;

    public function lockById(int $id): ?CashReconciliation;

    public function findByIdempotencyKey(string $key): ?CashReconciliation;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): CashReconciliation;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(CashReconciliation $reconciliation, array $attributes): CashReconciliation;

    /**
     * @return Builder<CashReconciliation>
     */
    public function datatableQuery(CashReconciliationFilterData $filters): Builder;
}
