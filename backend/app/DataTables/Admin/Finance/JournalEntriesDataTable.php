<?php

namespace App\DataTables\Admin\Finance;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Finance\JournalEntryFilterData;
use App\Models\JournalEntry;
use App\Repositories\Contracts\Finance\JournalEntryRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Journal entries listing (filters: type, reference, entry id or key, account, reversals, posted range). Read-only.
 */
final class JournalEntriesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'journal-entries-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly JournalEntryRepositoryInterface $entries)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<JournalEntry>
     */
    public function query(): Builder
    {
        return $this->entries->datatableQuery(JournalEntryFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('entry', __('admin.ledger.entries.fields.entry')),
            $this->column('type', __('admin.ledger.entries.fields.type'), orderable: true),
            $this->column('description', __('admin.ledger.entries.fields.description')),
            $this->column('reference', __('admin.ledger.entries.fields.reference')),
            $this->column('lines_count', __('admin.ledger.entries.fields.lines')),
            $this->column('posted_at', __('admin.ledger.entries.fields.posted'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->addColumn('entry', static fn (JournalEntry $entry): string => '<a href="'.e(route('admin.journal-entries.show', $entry)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e(Str::substr($entry->uuid, 0, 8)).'</a>'
            .($entry->reversal_of_id !== null ? ' <span class="text-xs text-warning-700">'.e(__('admin.ledger.entries.is_reversal')).'</span>' : ''));
        $this->rawColumn('entry');
        $table->addColumn('type', static fn (JournalEntry $entry): string => Blade::renderComponent(new Badge($entry->type)));
        $this->rawColumn('type');
        $table->addColumn('description', static fn (JournalEntry $entry): string => e((string) __('admin.'.$entry->description_key)));
        $table->addColumn('reference', static fn (JournalEntry $entry): string => $entry->reference_type === null
            ? '—'
            : e((string) ($entry->meta['booking'] ?? ($entry->reference_type.' #'.$entry->reference_id))));
        $table->addColumn('lines_count', static fn (JournalEntry $entry): int => (int) $entry->getAttribute('lines_count'));
        $this->dateColumn($table, 'posted_at');

        $this->actionsColumn($table, static fn (JournalEntry $entry): array => [
            TableAction::view(route('admin.journal-entries.show', $entry), $entry),
        ]);
    }
}
