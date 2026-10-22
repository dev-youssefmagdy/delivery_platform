<?php

namespace App\Repositories\Eloquent\Finance;

use App\DTOs\Finance\AdjustmentFilterData;
use App\Enums\Finance\AdjustmentDirection;
use App\Enums\Finance\ApprovalStatus;
use App\Models\FinancialAdjustmentRequest;
use App\Repositories\Contracts\Finance\FinancialAdjustmentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class FinancialAdjustmentRepository implements FinancialAdjustmentRepositoryInterface
{
    public function find(int $id): ?FinancialAdjustmentRequest
    {
        return FinancialAdjustmentRequest::query()->find($id);
    }

    public function lockById(int $id): ?FinancialAdjustmentRequest
    {
        return FinancialAdjustmentRequest::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function findByIdempotencyKey(string $key): ?FinancialAdjustmentRequest
    {
        return FinancialAdjustmentRequest::query()->where('idempotency_key', $key)->first();
    }

    public function insert(array $attributes): FinancialAdjustmentRequest
    {
        $request = new FinancialAdjustmentRequest;
        $request->forceFill($attributes)->save();

        return $request;
    }

    public function update(FinancialAdjustmentRequest $request, array $attributes): FinancialAdjustmentRequest
    {
        $request->forceFill($attributes)->save();

        return $request;
    }

    public function pendingCount(): int
    {
        return FinancialAdjustmentRequest::query()->where('status', ApprovalStatus::Pending->value)->count();
    }

    public function datatableQuery(AdjustmentFilterData $filters): Builder
    {
        return FinancialAdjustmentRequest::query()
            ->select('financial_adjustment_requests.*')
            ->with(['account:id,uuid,type,owner_type,owner_id,currency', 'requestedBy:id,name', 'decidedBy:id,name'])
            ->when($filters->accountId !== null, static fn (Builder $query) => $query->where('financial_adjustment_requests.ledger_account_id', $filters->accountId))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('financial_adjustment_requests.status', array_map(static fn (ApprovalStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->directions !== [], static fn (Builder $query) => $query->whereIn('financial_adjustment_requests.direction', array_map(static fn (AdjustmentDirection $direction): string => $direction->value, $filters->directions)))
            ->when($filters->requesterIds !== [], static fn (Builder $query) => $query->whereIn('financial_adjustment_requests.requested_by_id', $filters->requesterIds))
            ->when($filters->search !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('financial_adjustment_requests.uuid', $filters->search)
                ->orWhere('financial_adjustment_requests.reference', $filters->search)
                ->orWhereIn('financial_adjustment_requests.ledger_account_id', static fn ($sub) => $sub->select('id')->from('ledger_accounts')->where('uuid', $filters->search))))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('financial_adjustment_requests.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('financial_adjustment_requests.created_at', '<=', $filters->to?->endOfDay()));
    }
}
