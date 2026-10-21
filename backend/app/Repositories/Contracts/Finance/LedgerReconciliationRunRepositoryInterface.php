<?php

namespace App\Repositories\Contracts\Finance;

use App\Models\LedgerReconciliationRun;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reconciliation run records: inserted once, never edited.
 */
interface LedgerReconciliationRunRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): LedgerReconciliationRun;

    public function latest(): ?LedgerReconciliationRun;

    public function findByUuid(string $uuid): ?LedgerReconciliationRun;

    /**
     * @return Builder<LedgerReconciliationRun>
     */
    public function datatableQuery(?string $status = null): Builder;
}
