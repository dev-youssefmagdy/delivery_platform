<?php

namespace App\Repositories\Eloquent\Payout;

use App\DTOs\Payout\PayoutBatchFilterData;
use App\Enums\Payout\PayoutBatchStatus;
use App\Models\PayoutBatch;
use App\Repositories\Contracts\Payout\PayoutBatchRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class PayoutBatchRepository implements PayoutBatchRepositoryInterface
{
    public function find(int $id): ?PayoutBatch
    {
        return PayoutBatch::query()->find($id);
    }

    public function lockById(int $id): ?PayoutBatch
    {
        return PayoutBatch::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function insert(array $attributes): PayoutBatch
    {
        $batch = new PayoutBatch;
        $batch->forceFill($attributes)->save();

        return $batch;
    }

    public function update(PayoutBatch $batch, array $attributes): PayoutBatch
    {
        $batch->forceFill($attributes)->save();

        return $batch;
    }

    public function datatableQuery(PayoutBatchFilterData $filters): Builder
    {
        return PayoutBatch::query()
            ->select('payout_batches.*')
            ->with('createdBy:id,name')
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('payout_batches.status', array_map(static fn (PayoutBatchStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->search !== null, static fn (Builder $query) => $query->where('payout_batches.reference', 'like', $filters->search.'%'))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('payout_batches.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('payout_batches.created_at', '<=', $filters->to?->endOfDay()));
    }
}
