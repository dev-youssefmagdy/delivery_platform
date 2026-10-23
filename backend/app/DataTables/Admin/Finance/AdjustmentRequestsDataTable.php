<?php

namespace App\DataTables\Admin\Finance;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Finance\Concerns\FormatsLedgerMoney;
use App\DTOs\Finance\AdjustmentFilterData;
use App\Models\FinancialAdjustmentRequest;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\FinancialAdjustmentRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Adjustment requests (maker-checker). Approve / reject appear only for admins who may decide (never the requester); the requester
 * can cancel their own pending request.
 */
final class AdjustmentRequestsDataTable extends AdminDataTable
{
    use FormatsLedgerMoney;

    public const TABLE_ID = 'adjustments-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly FinancialAdjustmentRepositoryInterface $adjustments)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<FinancialAdjustmentRequest>
     */
    public function query(): Builder
    {
        return $this->adjustments->datatableQuery(AdjustmentFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'financial_adjustment_requests.id'),
            $this->column('request', __('admin.adjustments.fields.request')),
            $this->column('wallet', __('admin.adjustments.fields.wallet')),
            $this->column('direction', __('admin.adjustments.fields.direction')),
            $this->column('amount', __('admin.adjustments.fields.amount'), orderable: true, name: 'financial_adjustment_requests.amount_minor'),
            $this->column('status', __('admin.adjustments.fields.status'), orderable: true, name: 'financial_adjustment_requests.status'),
            $this->column('requested_by', __('admin.adjustments.fields.requested_by')),
            $this->column('decided_by', __('admin.adjustments.fields.decided_by')),
            $this->column('created_at', __('admin.adjustments.fields.created'), orderable: true, name: 'financial_adjustment_requests.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'request', 'wallet', 'direction', 'amount', 'status', 'requested_by', 'decided_by', 'created_at', 'actions']);

        $table->addColumn('request', static fn (FinancialAdjustmentRequest $request): string => '<a href="'.e(route('admin.adjustment-requests.show', $request)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e(Str::substr($request->uuid, 0, 8)).'</a>');
        $this->rawColumn('request');
        $table->addColumn('wallet', static function (FinancialAdjustmentRequest $request): string {
            $account = $request->account;
            $owner = $account->owner;
            $name = $owner instanceof User ? $owner->name : ($owner instanceof ProviderProfile ? $owner->user->name : null);

            return e(($name ?? __('admin.ledger.accounts.unnamed')).' · '.$account->type->label());
        });
        $table->editColumn('direction', static fn (FinancialAdjustmentRequest $request): string => $request->direction->label());
        $table->editColumn('amount', fn (FinancialAdjustmentRequest $request): string => $this->formatMinor($request->amount_minor, $request->currency));
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('requested_by', static fn (FinancialAdjustmentRequest $request): string => e((string) ($request->requestedBy->name ?? '—')));
        $table->addColumn('decided_by', static fn (FinancialAdjustmentRequest $request): string => e((string) ($request->decidedBy->name ?? '—')));
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (FinancialAdjustmentRequest $request): array => [
            TableAction::view(route('admin.adjustment-requests.show', $request), $request),
            TableAction::confirm(
                __('admin.adjustments.actions.approve'),
                route('admin.adjustment-requests.approve', $request),
                'approve',
                $request,
                icon: 'check',
                variant: 'success',
                confirm: ['title' => __('admin.adjustments.confirm.approve_title'), 'message' => __('admin.adjustments.confirm.approve_message'), 'confirm_label' => __('admin.adjustments.actions.approve')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.adjustments.actions.reject'),
                route('admin.adjustment-requests.reject', $request),
                'reject',
                $request,
                icon: 'x-mark',
                reasonRequired: true,
                confirm: ['title' => __('admin.adjustments.confirm.reject_title'), 'message' => __('admin.adjustments.confirm.reject_message'), 'confirm_label' => __('admin.adjustments.actions.reject')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.adjustments.actions.cancel'),
                route('admin.adjustment-requests.cancel', $request),
                'cancel',
                $request,
                icon: 'x-mark',
                variant: 'warning',
                reasonRequired: true,
                confirm: ['title' => __('admin.adjustments.confirm.cancel_title'), 'message' => __('admin.adjustments.confirm.cancel_message'), 'confirm_label' => __('admin.adjustments.actions.cancel')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
