<?php

namespace App\Repositories\Eloquent\Finance;

use App\DTOs\Finance\CashReconciliationFilterData;
use App\Enums\Finance\CashReconciliationStatus;
use App\Models\CashReconciliation;
use App\Repositories\Contracts\Finance\CashReconciliationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class CashReconciliationRepository implements CashReconciliationRepositoryInterface
{
    public function find(int $id): ?CashReconciliation
    {
        return CashReconciliation::query()->find($id);
    }

    public function lockById(int $id): ?CashReconciliation
    {
        return CashReconciliation::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function findByIdempotencyKey(string $key): ?CashReconciliation
    {
        return CashReconciliation::query()->where('idempotency_key', $key)->first();
    }

    public function insert(array $attributes): CashReconciliation
    {
        $reconciliation = new CashReconciliation;
        $reconciliation->forceFill($attributes)->save();

        return $reconciliation;
    }

    public function update(CashReconciliation $reconciliation, array $attributes): CashReconciliation
    {
        $reconciliation->forceFill($attributes)->save();

        return $reconciliation;
    }

    public function datatableQuery(CashReconciliationFilterData $filters): Builder
    {
        return CashReconciliation::query()
            ->select('cash_reconciliations.*')
            ->with(['provider.user:id,uuid,name', 'createdBy:id,name', 'settledBy:id,name'])
            ->when($filters->providerId !== null, static fn (Builder $query) => $query->where('cash_reconciliations.provider_profile_id', $filters->providerId))
            ->when($filters->providerIds !== [], static fn (Builder $query) => $query->whereIn('cash_reconciliations.provider_profile_id', $filters->providerIds))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('cash_reconciliations.status', array_map(static fn (CashReconciliationStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('cash_reconciliations.period_to', '>=', $filters->from?->toDateString()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('cash_reconciliations.period_from', '<=', $filters->to?->toDateString()));
    }
}
