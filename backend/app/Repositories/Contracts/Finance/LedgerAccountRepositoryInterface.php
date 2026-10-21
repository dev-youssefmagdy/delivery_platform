<?php

namespace App\Repositories\Contracts\Finance;

use App\DTOs\Finance\LedgerAccountFilterData;
use App\Enums\Finance\LedgerAccountType;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Ledger accounts. Deliberately not a BaseRepository: there is no delete, and the only update is the cached balance,
 * written under the row lock by LedgerService (Plan I5, I6).
 */
interface LedgerAccountRepositoryInterface
{
    public function find(int $id): ?LedgerAccount;

    public function findByUuid(string $uuid): ?LedgerAccount;

    public function findByIdentity(LedgerAccountType $type, string $scopeKey, string $currency): ?LedgerAccount;

    /**
     * The account of this identity, created with the type's default negative-balance policy when it does not exist yet.
     * Safe under concurrency: the unique identity decides, the loser reads the winner's row.
     */
    public function firstOrCreateFor(LedgerAccountType $type, ?int $ownerId, string $currency): LedgerAccount;

    /**
     * Locks the accounts FOR UPDATE in ascending id order (the single deterministic order that makes concurrent
     * postings deadlock-free). Must be called inside a transaction. Keyed by id.
     *
     * @param list<int> $ids
     * @return Collection<int, LedgerAccount>
     */
    public function lockInOrder(array $ids): Collection;

    /**
     * Writes the new cached balance of an account the caller holds locked.
     */
    public function writeBalance(LedgerAccount $locked, int $balanceMinor): void;

    /**
     * Every account of an owner (one per currency), oldest first.
     *
     * @return Collection<int, LedgerAccount>
     */
    public function forOwner(LedgerAccountType $type, int $ownerId): Collection;

    /**
     * @return Builder<LedgerAccount>
     */
    public function datatableQuery(LedgerAccountFilterData $filters): Builder;

    /**
     * Cached balances of every account, for reconciliation: id, uuid, type and balance.
     *
     * @return Collection<int, LedgerAccount>
     */
    public function allForReconciliation(): Collection;
}
