<?php

namespace App\Http\Controllers\Admin\Finance;

use App\DataTables\Admin\Finance\ReconciliationRunsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Finance\RunReconciliationRequest;
use App\Models\LedgerReconciliationRun;
use App\Repositories\Contracts\Finance\LedgerReconciliationRunRepositoryInterface;
use App\Services\Finance\LedgerReconciliationService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Ledger reconciliation report (finance.view; running a check needs finance.reconcile). The nightly `ledger:reconcile`
 * and the button here do the same: compare, store, alert. Neither changes the ledger.
 */
final class ReconciliationController extends AdminController
{
    public function index(ReconciliationRunsDataTable $table, LedgerReconciliationRunRepositoryInterface $runs): mixed
    {
        return $table->render('admin.ledger-reconciliation.index', ['latest' => $runs->latest()]);
    }

    public function show(LedgerReconciliationRun $ledgerReconciliation): View
    {
        return view('admin.ledger-reconciliation.show', ['run' => $ledgerReconciliation]);
    }

    public function run(RunReconciliationRequest $request, LedgerReconciliationService $reconciliation): AdminFormResponse
    {
        $run = $reconciliation->run($this->actor());

        return $this->formResponse($run->findings === null ? __('admin.ledger.reconciliation.run_ok') : __('admin.ledger.reconciliation.run_mismatch'))
            ->redirect(route('admin.ledger-reconciliation.show', $run));
    }
}
