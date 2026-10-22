<?php

namespace App\Repositories\Eloquent\Payout;

use App\DTOs\Payout\PayoutFilterData;
use App\Enums\Payout\PayoutStatus;
use App\Models\PayoutRequest;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class PayoutRequestRepository implements PayoutRequestRepositoryInterface
{
    public function find(int $id): ?PayoutRequest
    {
        return PayoutRequest::query()->find($id);
    }

    public function findByUuid(string $uuid): ?PayoutRequest
    {
        return PayoutRequest::query()->where('uuid', $uuid)->first();
    }

    public function findForProvider(int $providerId, string $uuid): ?PayoutRequest
    {
        return PayoutRequest::query()->where('provider_profile_id', $providerId)->where('uuid', $uuid)->first();
    }

    public function lockById(int $id): ?PayoutRequest
    {
        return PayoutRequest::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function lockMany(array $ids): Collection
    {
        return PayoutRequest::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    public function findByIdempotencyKey(int $providerId, string $key): ?PayoutRequest
    {
        return PayoutRequest::query()->where('provider_profile_id', $providerId)->where('idempotency_key', $key)->first();
    }

    public function insert(array $attributes): PayoutRequest
    {
        $payout = new PayoutRequest;
        $payout->forceFill($attributes)->save();

        return $payout;
    }

    public function update(PayoutRequest $payout, array $attributes): PayoutRequest
    {
        $payout->forceFill($attributes)->save();

        return $payout;
    }

    public function reservedMinor(int $providerId, string $currency): int
    {
        return (int) PayoutRequest::query()
            ->where('provider_profile_id', $providerId)
            ->where('currency', $currency)
            ->whereIn('status', $this->values(PayoutStatus::open()))
            ->sum('amount_minor');
    }

    public function openCount(int $providerId): int
    {
        return PayoutRequest::query()->where('provider_profile_id', $providerId)->whereIn('status', $this->values(PayoutStatus::open()))->count();
    }

    public function openCountForMethod(int $methodId): int
    {
        return PayoutRequest::query()->where('payout_method_id', $methodId)->whereIn('status', $this->values(PayoutStatus::open()))->count();
    }

    public function awaitingDecisionCount(): int
    {
        return PayoutRequest::query()->whereIn('status', $this->values(PayoutStatus::awaitingDecision()))->count();
    }

    public function pageForProvider(int $providerId, int $perPage): LengthAwarePaginator
    {
        return PayoutRequest::query()->where('provider_profile_id', $providerId)->latest('id')->paginate($perPage);
    }

    public function datatableQuery(PayoutFilterData $filters): Builder
    {
        return PayoutRequest::query()
            ->select('payout_requests.*')
            ->with(['provider.user:id,uuid,name', 'approvedBy:id,name', 'batch:id,uuid,reference'])
            ->when($filters->batchId !== null, static fn (Builder $query) => $query->where('payout_requests.payout_batch_id', $filters->batchId))
            ->when($filters->providerId !== null, static fn (Builder $query) => $query->where('payout_requests.provider_profile_id', $filters->providerId))
            ->when($filters->statuses !== [], fn (Builder $query) => $query->whereIn('payout_requests.status', $this->values($filters->statuses)))
            ->when($filters->providerIds !== [], static fn (Builder $query) => $query->whereIn('payout_requests.provider_profile_id', $filters->providerIds))
            ->when($filters->approverIds !== [], static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->whereIn('payout_requests.approved_by_id', $filters->approverIds)
                ->orWhereIn('payout_requests.first_approved_by_id', $filters->approverIds)))
            ->when($filters->amountMinMinor !== null, static fn (Builder $query) => $query->where('payout_requests.amount_minor', '>=', $filters->amountMinMinor))
            ->when($filters->amountMaxMinor !== null, static fn (Builder $query) => $query->where('payout_requests.amount_minor', '<=', $filters->amountMaxMinor))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('payout_requests.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('payout_requests.created_at', '<=', $filters->to?->endOfDay()))
            ->when($filters->search !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('payout_requests.uuid', $filters->search)
                ->orWhere('payout_requests.transfer_reference', $filters->search)));
    }

    public function forBatch(int $batchId): Collection
    {
        return PayoutRequest::query()->with(['provider.user', 'method'])->where('payout_batch_id', $batchId)->orderBy('id')->get();
    }

    /**
     * @param list<PayoutStatus> $statuses
     * @return list<string>
     */
    private function values(array $statuses): array
    {
        return array_map(static fn (PayoutStatus $status): string => $status->value, $statuses);
    }
}
