<?php

namespace App\Repositories\Eloquent\Finance;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class JournalLineRepository implements JournalLineRepositoryInterface
{
    public function insertFor(JournalEntry $entry, array $rows): void
    {
        $now = now();

        DB::table('journal_lines')->insert(array_map(static fn (array $row): array => $row + ['journal_entry_id' => $entry->id, 'created_at' => $now], $rows));
    }

    public function forEntry(JournalEntry $entry): Collection
    {
        return JournalLine::query()->with('account')->where('journal_entry_id', $entry->id)->orderBy('id')->get();
    }

    public function statementQuery(array $accountIds): Builder
    {
        return JournalLine::query()
            ->with(['entry:id,uuid,type,description_key,reference_type,reference_id,posted_at,reversal_of_id', 'account:id,uuid,type,currency'])
            ->whereIn('ledger_account_id', $accountIds === [] ? [0] : $accountIds);
    }

    public function totalsByAccount(): array
    {
        $totals = [];

        foreach (DB::table('journal_lines')->select('ledger_account_id', 'side', DB::raw('SUM(amount_minor) as total'))->groupBy('ledger_account_id', 'side')->get() as $row) {
            $totals[(int) $row->ledger_account_id] ??= ['debit' => 0, 'credit' => 0];
            $totals[(int) $row->ledger_account_id][(string) $row->side === 'debit' ? 'debit' : 'credit'] = (int) $row->total;
        }

        return $totals;
    }

    public function unbalancedEntries(): array
    {
        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->select('journal_lines.journal_entry_id', 'journal_entries.uuid', 'journal_lines.currency', DB::raw("SUM(CASE WHEN journal_lines.side = 'debit' THEN journal_lines.amount_minor ELSE -journal_lines.amount_minor END) as difference"))
            ->groupBy('journal_lines.journal_entry_id', 'journal_entries.uuid', 'journal_lines.currency')
            ->havingRaw('difference <> 0')
            ->get();

        return $rows->map(static fn (object $row): array => [
            'entry_id' => (int) $row->journal_entry_id,
            'uuid' => (string) $row->uuid,
            'currency' => (string) $row->currency,
            'difference' => (int) $row->difference,
        ])->all();
    }

    public function currencyMismatches(): array
    {
        $rows = DB::table('journal_lines')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereColumn('journal_lines.currency', '<>', 'ledger_accounts.currency')
            ->get(['journal_lines.id as line_id', 'journal_entries.uuid as entry_uuid', 'ledger_accounts.uuid as account_uuid', 'journal_lines.currency as line_currency', 'ledger_accounts.currency as account_currency']);

        return $rows->map(static fn (object $row): array => [
            'line_id' => (int) $row->line_id,
            'entry_uuid' => (string) $row->entry_uuid,
            'account_uuid' => (string) $row->account_uuid,
            'line' => (string) $row->line_currency,
            'account' => (string) $row->account_currency,
        ])->all();
    }
}
