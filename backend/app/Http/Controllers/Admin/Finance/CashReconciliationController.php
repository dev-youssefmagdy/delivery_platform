<?php

namespace App\Http\Controllers\Admin\Finance;

use App\DataTables\Admin\Finance\CashReconciliationsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Finance\CancelCashReconciliationRequest;
use App\Http\Requests\Admin\Finance\OpenCashReconciliationRequest;
use App\Http\Requests\Admin\Finance\SettleCashReconciliationRequest;
use App\Models\CashReconciliation;
use App\Services\Audit\AuditTrailService;
use App\Services\Finance\CashReconciliationService;
use App\Services\Finance\WalletOverviewService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Cash reconciliation (finance.view / finance.reconcile): summarise a provider's cash jobs for a period, then record the cash
 * commission the provider handed over; the settlement is a new ledger entry and the debt shown is the live wallet.
 */
final class CashReconciliationController extends AdminController
{
    public function __construct(private readonly CashReconciliationService $reconciliations) {}

    public function index(CashReconciliationsDataTable $table): mixed
    {
        return $table->render('admin.cash-reconciliations.index');
    }

    public function show(CashReconciliation $cashReconciliation, AuditTrailService $trail, WalletOverviewService $wallets): View
    {
        $cashReconciliation->load(['provider.user', 'createdBy', 'settledBy']);

        return view('admin.cash-reconciliations.show', [
            'reconciliation' => $cashReconciliation,
            'overview' => $wallets->forProvider($cashReconciliation->provider),
            'logs' => $trail->latestFor($cashReconciliation),
        ]);
    }

    public function store(OpenCashReconciliationRequest $request): AdminFormResponse
    {
        $reconciliation = $this->reconciliations->open($this->actor(), $request->toDto());

        return $this->formResponse(__('admin.cash.opened'))->redirect(route('admin.cash-reconciliations.show', $reconciliation));
    }

    public function settle(SettleCashReconciliationRequest $request, CashReconciliation $cashReconciliation): AdminFormResponse
    {
        $this->reconciliations->settle($this->actor(), $cashReconciliation, $request->toDto());

        return $this->formResponse(__('admin.cash.settled_done'))->reload();
    }

    public function cancel(CancelCashReconciliationRequest $request, CashReconciliation $cashReconciliation): AdminFormResponse
    {
        $this->reconciliations->cancel($this->actor(), $cashReconciliation, (string) $request->reason());

        return $this->formResponse(__('admin.cash.cancelled'))->reload();
    }
}
