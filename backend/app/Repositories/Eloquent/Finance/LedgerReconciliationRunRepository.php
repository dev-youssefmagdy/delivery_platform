<?php

namespace App\Repositories\Eloquent\Finance;

use App\Models\LedgerReconciliationRun;
use App\Repositories\Contracts\Finance\LedgerReconciliationRunRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class LedgerReconciliationRunRepository implements LedgerReconciliationRunRepositoryInterface
{
    public function create(array $attributes): LedgerReconciliationRun
    {
        return LedgerReconciliationRun::query()->create($attributes);
    }

    public function latest(): ?LedgerReconciliationRun
    {
        return LedgerReconciliationRun::query()->orderByDesc('ran_at')->orderByDesc('id')->first();
    }

    public function findByUuid(string $uuid): ?LedgerReconciliationRun
    {
        return LedgerReconciliationRun::query()->where('uuid', $uuid)->first();
    }

    public function datatableQuery(?string $status = null): Builder
    {
        return LedgerReconciliationRun::query()->when($status !== null, static fn (Builder $query) => $query->where('status', $status));
    }
}
