<?php

namespace App\DataTables\Admin\Finance;

use App\DataTables\Admin\AdminDataTable;
use App\Models\LedgerReconciliationRun;
use App\Repositories\Contracts\Finance\LedgerReconciliationRunRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * History of reconciliation runs, newest first. Filter: status. Read-only.
 */
final class ReconciliationRunsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'ledger-reconciliation-runs-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly LedgerReconciliationRunRepositoryInterface $runs)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<LedgerReconciliationRun>
     */
    public function query(): Builder
    {
        $status = $this->filterRequest()->input('filters.status');

        return $this->runs->datatableQuery(is_string($status) && $status !== '' ? $status : null);
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('ran_at', __('admin.ledger.reconciliation.fields.ran_at'), orderable: true),
            $this->column('status', __('admin.ledger.reconciliation.fields.status'), orderable: true),
            $this->column('accounts_checked', __('admin.ledger.reconciliation.fields.accounts')),
            $this->column('entries_checked', __('admin.ledger.reconciliation.fields.entries')),
            $this->column('findings', __('admin.ledger.reconciliation.fields.findings')),
            $this->column('triggered_by', __('admin.ledger.reconciliation.fields.triggered_by')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->dateColumn($table, 'ran_at');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('findings', static fn (LedgerReconciliationRun $run): int => count($run->findings ?? []));
        $table->addColumn('triggered_by', static fn (LedgerReconciliationRun $run): string => e($run->triggered_by_type === 'admin' ? __('admin.ledger.reconciliation.by_admin') : __('admin.ledger.reconciliation.by_system')));
        $this->rawColumn('triggered_by');

        $this->actionsColumn($table, static fn (LedgerReconciliationRun $run): array => [
            TableAction::view(route('admin.ledger-reconciliation.show', $run), $run),
        ]);
    }
}
