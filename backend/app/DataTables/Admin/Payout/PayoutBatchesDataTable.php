<?php

namespace App\DataTables\Admin\Payout;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Finance\Concerns\FormatsLedgerMoney;
use App\DTOs\Payout\PayoutBatchFilterData;
use App\Models\PayoutBatch;
use App\Repositories\Contracts\Payout\PayoutBatchRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Payout batches (filters: status, reference, created range).
 */
final class PayoutBatchesDataTable extends AdminDataTable
{
    use FormatsLedgerMoney;

    public const TABLE_ID = 'payout-batches-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly PayoutBatchRepositoryInterface $batches)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<PayoutBatch>
     */
    public function query(): Builder
    {
        return $this->batches->datatableQuery(PayoutBatchFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'payout_batches.id'),
            $this->column('reference', __('admin.batches.fields.reference'), orderable: true, name: 'payout_batches.reference'),
            $this->column('status', __('admin.batches.fields.status'), orderable: true, name: 'payout_batches.status'),
            $this->column('payout_count', __('admin.batches.fields.count'), orderable: true, name: 'payout_batches.payout_count'),
            $this->column('total', __('admin.batches.fields.total')),
            $this->column('created_by', __('admin.batches.fields.created_by')),
            $this->column('created_at', __('admin.batches.fields.created'), orderable: true, name: 'payout_batches.created_at'),
            $this->column('exported_at', __('admin.batches.fields.exported_at')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'reference', 'status', 'payout_count', 'total', 'created_by', 'created_at', 'exported_at', 'actions']);

        $table->editColumn('reference', static fn (PayoutBatch $batch): string => '<a href="'.e(route('admin.payout-batches.show', $batch)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e($batch->reference).'</a>');
        $this->rawColumn('reference');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('total', fn (PayoutBatch $batch): string => $this->formatMinor($batch->total_minor, $batch->currency));
        $table->addColumn('created_by', static fn (PayoutBatch $batch): string => e((string) ($batch->createdBy->name ?? '—')));
        $this->dateColumn($table, 'created_at');
        $this->dateColumn($table, 'exported_at');

        $this->actionsColumn($table, static fn (PayoutBatch $batch): array => [TableAction::view(route('admin.payout-batches.show', $batch), $batch)]);
    }
}
