<?php

namespace App\DataTables\Admin\Payments;

use App\DataTables\Admin\AdminDataTable;
use App\Models\PaymentReconciliationRun;
use App\Repositories\Contracts\Payments\PaymentReconciliationRunRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Payment reconciliation runs (filter: result). Read-only.
 */
final class PaymentReconciliationRunsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'payment-reconciliation-runs-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly PaymentReconciliationRunRepositoryInterface $runs)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<PaymentReconciliationRun>
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
            $this->column('ran_at', __('admin.payment_reconciliation.fields.ran_at'), orderable: true),
            $this->column('status', __('admin.payment_reconciliation.fields.status'), orderable: true),
            $this->column('payments_checked', __('admin.payment_reconciliation.fields.payments')),
            $this->column('transactions_checked', __('admin.payment_reconciliation.fields.transactions')),
            $this->column('timed_out', __('admin.payment_reconciliation.fields.timed_out')),
            $this->column('findings', __('admin.payment_reconciliation.fields.findings')),
            $this->column('triggered_by', __('admin.payment_reconciliation.fields.triggered_by')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->dateColumn($table, 'ran_at');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('findings', static fn (PaymentReconciliationRun $run): int => count($run->findings ?? []));
        $table->addColumn('triggered_by', static fn (PaymentReconciliationRun $run): string => e($run->triggered_by_type === 'admin' ? __('admin.ledger.reconciliation.by_admin') : __('admin.ledger.reconciliation.by_system')));
        $this->rawColumn('triggered_by');

        $this->actionsColumn($table, static fn (PaymentReconciliationRun $run): array => [
            TableAction::view(route('admin.payment-reconciliation.show', $run), $run),
        ]);
    }
}
