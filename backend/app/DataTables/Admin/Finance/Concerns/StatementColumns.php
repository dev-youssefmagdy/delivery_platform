<?php

namespace App\DataTables\Admin\Finance\Concerns;

use App\Models\JournalLine;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

/**
 * The statement of one or more ledger accounts: one row per journal line with the change it made to the account (in the
 * account's natural sign, so a wallet credit is positive) and the balance right after it. Shared by the account page and
 * the customer / provider wallet tabs.
 *
 * @mixin \App\DataTables\Admin\AdminDataTable
 */
trait StatementColumns
{
    use FormatsLedgerMoney;

    /**
     * @return list<Column>
     */
    protected function statementColumns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('posted_at', __('admin.ledger.statement.posted')),
            $this->column('type', __('admin.ledger.statement.type')),
            $this->column('description', __('admin.ledger.statement.description')),
            $this->column('change', __('admin.ledger.statement.change')),
            $this->column('balance_after_minor', __('admin.ledger.statement.balance_after')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configureStatement(EloquentDataTable $table): void
    {
        $table->addColumn('posted_at', fn (JournalLine $line): string => $this->formatDate($line->entry->posted_at));
        $table->addColumn('type', static fn (JournalLine $line): string => Blade::renderComponent(new Badge($line->entry->type)));
        $this->rawColumn('type');
        $table->addColumn('description', static fn (JournalLine $line): string => e((string) __('admin.'.$line->entry->description_key)));
        $table->addColumn('change', function (JournalLine $line): string {
            $signed = $line->side === $line->account->type->normalSide() ? $line->amount_minor : -$line->amount_minor;
            $cell = $this->balanceCell($signed, $line->currency);

            return $signed > 0 ? str_replace('text-slate-900', 'text-success-700', $cell) : $cell;
        });
        $this->rawColumn('change');
        $table->editColumn('balance_after_minor', fn (Model $row): string => $row instanceof JournalLine ? $this->balanceCell($row->balance_after_minor, $row->currency) : '');
        $this->rawColumn('balance_after_minor');

        $this->actionsColumn($table, static fn (JournalLine $line): array => [
            TableAction::view(route('admin.journal-entries.show', $line->entry), $line->entry),
        ]);
    }
}
