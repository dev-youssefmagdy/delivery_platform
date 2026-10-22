<?php

namespace App\DataTables\Admin\Payout;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Finance\Concerns\FormatsLedgerMoney;
use App\DTOs\Payout\PayoutFilterData;
use App\Models\PayoutRequest;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Payout requests (filters of Plan Appendix C: status, provider, amount range, requested range, approver). Review, approve and
 * reject appear only where the policy allows them for the signed-in admin and the payout's state.
 */
class PayoutRequestsDataTable extends AdminDataTable
{
    use FormatsLedgerMoney;

    public const TABLE_ID = 'payouts-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly PayoutRequestRepositoryInterface $payouts)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return static::TABLE_ID;
    }

    /**
     * @return Builder<PayoutRequest>
     */
    public function query(): Builder
    {
        return $this->payouts->datatableQuery($this->scoped(PayoutFilterData::fromRequest($this->filterRequest())));
    }

    protected function scoped(PayoutFilterData $filters): PayoutFilterData
    {
        return $filters;
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'payout_requests.id'),
            $this->column('payout', __('admin.payouts.fields.payout')),
            $this->column('provider', __('admin.payouts.fields.provider')),
            $this->column('amount', __('admin.payouts.fields.amount'), orderable: true, name: 'payout_requests.amount_minor'),
            $this->column('net', __('admin.payouts.fields.net')),
            $this->column('status', __('admin.payouts.fields.status'), orderable: true, name: 'payout_requests.status'),
            $this->column('requires_dual_approval', __('admin.payouts.fields.dual')),
            $this->column('approver', __('admin.payouts.fields.approver')),
            $this->column('batch', __('admin.payouts.fields.batch')),
            $this->column('created_at', __('admin.payouts.fields.requested_at'), orderable: true, name: 'payout_requests.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'payout', 'provider', 'amount', 'net', 'status', 'requires_dual_approval', 'approver', 'batch', 'created_at', 'actions']);

        $table->addColumn('payout', static fn (PayoutRequest $payout): string => '<a href="'.e(route('admin.payout-requests.show', $payout)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e(Str::substr($payout->uuid, 0, 8)).'</a>');
        $this->rawColumn('payout');
        $table->addColumn('provider', static function (PayoutRequest $payout): string {
            $name = e((string) ($payout->provider->user->name ?? __('admin.providers.unnamed')));

            return Gate::allows('view', $payout->provider) ? '<a href="'.e(route('admin.providers.show', $payout->provider)).'" class="text-brand-700 hover:underline">'.$name.'</a>' : $name;
        });
        $this->rawColumn('provider');
        $table->editColumn('amount', fn (PayoutRequest $payout): string => $this->formatMinor($payout->amount_minor, $payout->currency));
        $table->addColumn('net', fn (PayoutRequest $payout): string => $this->formatMinor($payout->net_minor, $payout->currency));
        $this->enumBadgeColumn($table, 'status');
        $this->boolColumn($table, 'requires_dual_approval');
        $table->addColumn('approver', static fn (PayoutRequest $payout): string => e((string) ($payout->approvedBy->name ?? '—')));
        $table->addColumn('batch', static fn (PayoutRequest $payout): string => $payout->batch === null ? '—' : '<a href="'.e(route('admin.payout-batches.show', $payout->batch)).'" class="ltr-only text-brand-700 hover:underline">'.e($payout->batch->reference).'</a>');
        $this->rawColumn('batch');
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (PayoutRequest $payout): array => [
            TableAction::view(route('admin.payout-requests.show', $payout), $payout),
            TableAction::confirm(
                __('admin.payouts.actions.review'),
                route('admin.payout-requests.review', $payout),
                'review',
                $payout,
                icon: 'eye',
                variant: 'primary',
                confirm: ['title' => __('admin.payouts.confirm.review_title'), 'message' => __('admin.payouts.confirm.review_message'), 'confirm_label' => __('admin.payouts.actions.review')],
                reloadTable: static::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.payouts.actions.approve'),
                route('admin.payout-requests.approve', $payout),
                'approve',
                $payout,
                icon: 'check',
                variant: 'success',
                confirm: ['title' => __('admin.payouts.confirm.approve_title'), 'message' => __('admin.payouts.confirm.approve_message'), 'confirm_label' => __('admin.payouts.actions.approve')],
                reloadTable: static::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.payouts.actions.reject'),
                route('admin.payout-requests.reject', $payout),
                'reject',
                $payout,
                icon: 'x-mark',
                reasonRequired: true,
                confirm: ['title' => __('admin.payouts.confirm.reject_title'), 'message' => __('admin.payouts.confirm.reject_message'), 'confirm_label' => __('admin.payouts.actions.reject')],
                reloadTable: static::TABLE_ID,
            ),
        ]);
    }
}
