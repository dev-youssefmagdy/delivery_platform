<?php

namespace App\Repositories\Contracts\Finance;

use App\DTOs\Finance\JournalEntryFilterData;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Builder;

/**
 * Journal entries. Append-only by construction: there is no update or delete method (Plan I5).
 */
interface JournalEntryRepositoryInterface
{
    public function findByUuid(string $uuid): ?JournalEntry;

    public function findByIdempotencyKey(string $key): ?JournalEntry;

    /**
     * Locks the entry row (a reversal serialises on the entry it reverses). Inside a transaction.
     */
    public function lockById(int $id): ?JournalEntry;

    /**
     * The entry that reversed this one, if any.
     */
    public function reversalOf(int $entryId): ?JournalEntry;

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): JournalEntry;

    /**
     * @return Builder<JournalEntry>
     */
    public function datatableQuery(JournalEntryFilterData $filters): Builder;

    public function count(): int;
}
