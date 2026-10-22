<?php

namespace App\Repositories\Eloquent\Finance;

use App\DTOs\Finance\JournalEntryFilterData;
use App\Models\JournalEntry;
use App\Repositories\Contracts\Finance\JournalEntryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class JournalEntryRepository implements JournalEntryRepositoryInterface
{
    public function find(int $id): ?JournalEntry
    {
        return JournalEntry::query()->find($id);
    }

    public function findByUuid(string $uuid): ?JournalEntry
    {
        return JournalEntry::query()->where('uuid', $uuid)->first();
    }

    public function findByIdempotencyKey(string $key): ?JournalEntry
    {
        return JournalEntry::query()->where('idempotency_key', $key)->first();
    }

    public function lockById(int $id): ?JournalEntry
    {
        return JournalEntry::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function reversalOf(int $entryId): ?JournalEntry
    {
        return JournalEntry::query()->where('reversal_of_id', $entryId)->first();
    }

    public function insert(array $attributes): JournalEntry
    {
        return JournalEntry::query()->create($attributes);
    }

    public function datatableQuery(JournalEntryFilterData $filters): Builder
    {
        return JournalEntry::query()
            ->withCount('lines')
            ->when($filters->types !== [], static fn (Builder $query) => $query->whereIn('type', array_map(static fn ($type): string => $type->value, $filters->types)))
            ->when($filters->reference !== null, static fn (Builder $query) => $query->where('reference_type', $filters->reference))
            ->when($filters->search !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('uuid', $filters->search)
                ->orWhere('idempotency_key', $filters->search)
                ->orWhere('idempotency_key', 'like', addcslashes((string) $filters->search, '\\%_').'%')))
            ->when($filters->accountUuid !== null, static fn (Builder $query) => $query->whereIn('id', static fn ($sub) => $sub
                ->select('journal_lines.journal_entry_id')->from('journal_lines')
                ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
                ->where('ledger_accounts.uuid', $filters->accountUuid)))
            ->when($filters->reversals === true, static fn (Builder $query) => $query->whereNotNull('reversal_of_id'))
            ->when($filters->reversals === false, static fn (Builder $query) => $query->whereNull('reversal_of_id'))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('posted_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('posted_at', '<=', $filters->to?->endOfDay()));
    }

    public function count(): int
    {
        return JournalEntry::query()->count();
    }
}
