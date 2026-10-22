<?php

namespace App\Repositories\Contracts\Payout;

use App\DTOs\Payout\PayoutFilterData;
use App\Models\PayoutRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Payout requests. Row-locking reads serve the state machine: every transition re-reads the row under FOR UPDATE.
 */
interface PayoutRequestRepositoryInterface
{
    public function find(int $id): ?PayoutRequest;

    public function findByUuid(string $uuid): ?PayoutRequest;

    public function findForProvider(int $providerId, string $uuid): ?PayoutRequest;

    public function lockById(int $id): ?PayoutRequest;

    /**
     * Locks several payouts FOR UPDATE in ascending id order (batches), keyed by id.
     *
     * @param list<int> $ids
     * @return Collection<int, PayoutRequest>
     */
    public function lockMany(array $ids): Collection;

    public function findByIdempotencyKey(int $providerId, string $key): ?PayoutRequest;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): PayoutRequest;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(PayoutRequest $payout, array $attributes): PayoutRequest;

    /**
     * Sum of the open (reserving) payouts of a provider in a currency.
     */
    public function reservedMinor(int $providerId, string $currency): int;

    public function openCount(int $providerId): int;

    public function openCountForMethod(int $methodId): int;

    /**
     * Ids of approved payouts that are not in a batch yet and share the currency of the oldest one, oldest first.
     *
     * @return list<int>
     */
    public function approvedUnbatchedIds(int $limit): array;

    public function awaitingDecisionCount(): int;

    /**
     * @return LengthAwarePaginator<int, PayoutRequest>
     */
    public function pageForProvider(int $providerId, int $perPage): LengthAwarePaginator;

    /**
     * @return Builder<PayoutRequest>
     */
    public function datatableQuery(PayoutFilterData $filters): Builder;

    /**
     * Payouts of a batch, oldest first (with provider user and method for the transfer file).
     *
     * @return Collection<int, PayoutRequest>
     */
    public function forBatch(int $batchId): Collection;
}
