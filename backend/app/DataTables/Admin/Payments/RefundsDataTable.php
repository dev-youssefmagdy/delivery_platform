<?php

namespace App\DataTables\Admin\Payments;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Payments\RefundFilterData;
use App\Models\PaymentRefund;
use App\Repositories\Contracts\Payments\PaymentRefundRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Refunds listing (filters: status, dual-approval flag, payment or booking, created range). Approve / reject appear only
 * for admins who may decide (never the requester).
 */
class RefundsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'refunds-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly PaymentRefundRepositoryInterface $refunds)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return static::TABLE_ID;
    }

    /**
     * @return Builder<PaymentRefund>
     */
    public function query(): Builder
    {
        return $this->refunds->datatableQuery($this->scoped(RefundFilterData::fromRequest($this->filterRequest())));
    }

    protected function scoped(RefundFilterData $filters): RefundFilterData
    {
        return $filters;
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'payment_refunds.id'),
            $this->column('refund', __('admin.refunds.fields.refund')),
            $this->column('booking', __('admin.refunds.fields.booking')),
            $this->column('amount', __('admin.refunds.fields.amount'), orderable: true, name: 'payment_refunds.amount_minor'),
            $this->column('status', __('admin.refunds.fields.status'), orderable: true, name: 'payment_refunds.status'),
            $this->column('requires_dual_approval', __('admin.refunds.fields.dual')),
            $this->column('requested_by', __('admin.refunds.fields.requested_by')),
            $this->column('decided_by', __('admin.refunds.fields.decided_by')),
            $this->column('created_at', __('admin.refunds.fields.created'), orderable: true, name: 'payment_refunds.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'refund', 'booking', 'amount', 'status', 'requires_dual_approval', 'requested_by', 'decided_by', 'created_at', 'actions']);

        $table->addColumn('refund', static fn (PaymentRefund $refund): string => '<a href="'.e(route('admin.payment-refunds.show', $refund)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e(Str::substr($refund->uuid, 0, 8)).'</a>');
        $this->rawColumn('refund');
        $table->addColumn('booking', static fn (PaymentRefund $refund): string => '<a href="'.e(route('admin.payments.show', $refund->payment)).'" class="ltr-only text-brand-700 hover:underline">'.e((string) ($refund->payment->booking->code ?? '—')).'</a>');
        $this->rawColumn('booking');
        $this->moneyColumn($table, 'amount');
        $this->enumBadgeColumn($table, 'status');
        $this->boolColumn($table, 'requires_dual_approval');
        $table->addColumn('requested_by', static fn (PaymentRefund $refund): string => e((string) ($refund->requestedBy->name ?? '—')));
        $table->addColumn('decided_by', static fn (PaymentRefund $refund): string => e((string) ($refund->decidedBy->name ?? '—')));
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (PaymentRefund $refund): array => [
            TableAction::view(route('admin.payment-refunds.show', $refund), $refund),
            TableAction::confirm(
                __('admin.refunds.actions.approve'),
                route('admin.payment-refunds.approve', $refund),
                'approve',
                $refund,
                icon: 'check',
                variant: 'success',
                confirm: ['title' => __('admin.refunds.confirm.approve_title'), 'message' => __('admin.refunds.confirm.approve_message'), 'confirm_label' => __('admin.refunds.actions.approve')],
                reloadTable: static::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.refunds.actions.reject'),
                route('admin.payment-refunds.reject', $refund),
                'reject',
                $refund,
                icon: 'x-mark',
                reasonRequired: true,
                confirm: ['title' => __('admin.refunds.confirm.reject_title'), 'message' => __('admin.refunds.confirm.reject_message'), 'confirm_label' => __('admin.refunds.actions.reject')],
                reloadTable: static::TABLE_ID,
            ),
        ]);
    }
}
