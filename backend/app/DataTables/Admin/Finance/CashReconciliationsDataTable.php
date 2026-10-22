<?php

namespace App\DataTables\Admin\Finance;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Finance\Concerns\FormatsLedgerMoney;
use App\DTOs\Finance\CashReconciliationFilterData;
use App\Models\CashReconciliation;
use App\Repositories\Contracts\Finance\CashReconciliationRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Cash reconciliations (filters: status, provider, period).
 */
final class CashReconciliationsDataTable extends AdminDataTable
{
    use FormatsLedgerMoney;

    public const TABLE_ID = 'cash-reconciliations-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly CashReconciliationRepositoryInterface $reconciliations)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<CashReconciliation>
     */
    public function query(): Builder
    {
        return $this->reconciliations->datatableQuery(CashReconciliationFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'cash_reconciliations.id'),
            $this->column('reconciliation', __('admin.cash.fields.reconciliation')),
            $this->column('provider', __('admin.cash.fields.provider')),
            $this->column('period', __('admin.cash.fields.period')),
            $this->column('cash_collected', __('admin.cash.fields.cash_collected')),
            $this->column('commission_due', __('admin.cash.fields.commission_due')),
            $this->column('settled', __('admin.cash.fields.settled')),
            $this->column('status', __('admin.cash.fields.status'), orderable: true, name: 'cash_reconciliations.status'),
            $this->column('created_at', __('admin.cash.fields.created'), orderable: true, name: 'cash_reconciliations.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'reconciliation', 'provider', 'period', 'cash_collected', 'commission_due', 'settled', 'status', 'created_at', 'actions']);

        $table->addColumn('reconciliation', static fn (CashReconciliation $item): string => '<a href="'.e(route('admin.cash-reconciliations.show', $item)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e(Str::substr($item->uuid, 0, 8)).'</a>');
        $this->rawColumn('reconciliation');
        $table->addColumn('provider', static fn (CashReconciliation $item): string => e((string) ($item->provider->user->name ?? __('admin.providers.unnamed'))));
        $table->addColumn('period', static fn (CashReconciliation $item): string => e($item->period_from->toDateString().' → '.$item->period_to->toDateString()));
        $table->addColumn('cash_collected', fn (CashReconciliation $item): string => $this->formatMinor($item->cash_collected_minor, $item->currency));
        $table->addColumn('commission_due', fn (CashReconciliation $item): string => $this->formatMinor($item->commission_due_minor, $item->currency));
        $table->addColumn('settled', fn (CashReconciliation $item): string => $this->formatMinor($item->settled_minor, $item->currency));
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (CashReconciliation $item): array => [TableAction::view(route('admin.cash-reconciliations.show', $item), $item)]);
    }
}
