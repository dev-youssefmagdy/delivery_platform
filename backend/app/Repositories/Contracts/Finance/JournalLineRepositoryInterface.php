<?php

namespace App\Repositories\Contracts\Finance;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Journal lines. Append-only like the entries; the read side serves statements and the reconciliation sums.
 */
interface JournalLineRepositoryInterface
{
    /**
     * @param list<array{ledger_account_id: int, side: string, amount_minor: int, currency: string, balance_after_minor: int}> $rows
     */
    public function insertFor(JournalEntry $entry, array $rows): void;

    /**
     * The lines of one entry with their accounts, in posting order.
     *
     * @return Collection<int, JournalLine>
     */
    public function forEntry(JournalEntry $entry): Collection;

    /**
     * Statement of accounts: lines with their entry, newest first by default.
     *
     * @param list<int> $accountIds
     * @return Builder<JournalLine>
     */
    public function statementQuery(array $accountIds): Builder;

    /**
     * Per account and side the sum of its lines: `[account_id => ['debit' => int, 'credit' => int]]`.
     *
     * @return array<int, array{debit: int, credit: int}>
     */
    public function totalsByAccount(): array;

    /**
     * Entries whose lines do not balance in some currency: `[{entry_id, uuid, currency, difference}]`.
     *
     * @return list<array{entry_id: int, uuid: string, currency: string, difference: int}>
     */
    public function unbalancedEntries(): array;

    /**
     * Lines whose currency differs from their account's: `[{line_id, entry_uuid, account_uuid, line, account}]`.
     *
     * @return list<array{line_id: int, entry_uuid: string, account_uuid: string, line: string, account: string}>
     */
    public function currencyMismatches(): array;
}
