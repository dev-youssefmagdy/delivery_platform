<?php

namespace App\Http\Controllers\Admin\Finance;

use App\DataTables\Admin\Finance\AdjustmentRequestsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Finance\ApproveAdjustmentRequest;
use App\Http\Requests\Admin\Finance\CancelAdjustmentRequest;
use App\Http\Requests\Admin\Finance\RejectAdjustmentRequest;
use App\Http\Requests\Admin\Finance\StoreAdjustmentRequest;
use App\Models\FinancialAdjustmentRequest;
use App\Services\Audit\AuditTrailService;
use App\Services\Finance\AdjustmentService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Wallet adjustment requests (maker-checker): `wallets.adjust` asks, a different admin with `wallets.approve_adjustment` decides.
 * Approval posts a new ledger entry; nothing is edited. Every step carries a reason and is audited.
 */
final class AdjustmentRequestController extends AdminController
{
    public function __construct(private readonly AdjustmentService $adjustments) {}

    public function index(AdjustmentRequestsDataTable $table): mixed
    {
        return $table->render('admin.adjustment-requests.index');
    }

    public function show(FinancialAdjustmentRequest $adjustmentRequest, AuditTrailService $trail): View
    {
        $adjustmentRequest->load(['account.owner', 'requestedBy', 'decidedBy']);

        return view('admin.adjustment-requests.show', ['adjustment' => $adjustmentRequest, 'logs' => $trail->latestFor($adjustmentRequest)]);
    }

    public function store(StoreAdjustmentRequest $request): AdminFormResponse
    {
        $this->adjustments->request($this->actor(), $request->toDto());

        return $this->formResponse(__('admin.adjustments.requested'))->closeModal()->reset()->reloadTable(AdjustmentRequestsDataTable::TABLE_ID);
    }

    public function approve(ApproveAdjustmentRequest $request, FinancialAdjustmentRequest $adjustmentRequest): AdminFormResponse
    {
        $this->adjustments->approve($this->actor(), $adjustmentRequest, $request->reason());

        return $this->done(__('admin.adjustments.approved'));
    }

    public function reject(RejectAdjustmentRequest $request, FinancialAdjustmentRequest $adjustmentRequest): AdminFormResponse
    {
        $this->adjustments->reject($this->actor(), $adjustmentRequest, (string) $request->reason());

        return $this->done(__('admin.adjustments.rejected'));
    }

    public function cancel(CancelAdjustmentRequest $request, FinancialAdjustmentRequest $adjustmentRequest): AdminFormResponse
    {
        $this->adjustments->cancel($this->actor(), $adjustmentRequest, (string) $request->reason());

        return $this->done(__('admin.adjustments.cancelled'));
    }

    private function done(string $message): AdminFormResponse
    {
        return $this->formResponse($message)->reloadTable(AdjustmentRequestsDataTable::TABLE_ID)->reload();
    }
}
