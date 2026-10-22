<?php

namespace App\Http\Controllers\Admin\Payments;

use App\DataTables\Admin\Payments\PaymentReconciliationRunsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Payments\RunPaymentReconciliationRequest;
use App\Models\PaymentReconciliationRun;
use App\Repositories\Contracts\Payments\GatewayWebhookEventRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentReconciliationRunRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRefundRepositoryInterface;
use App\Services\Payments\PaymentReconciliationService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Payment reconciliation report (finance.view / payments.view; running a check needs finance.reconcile): the hourly
 * `payments:reconcile` and the button do the same - sweep stale payments, compare with the gateway, store, alert. They never
 * change the ledger.
 */
final class PaymentReconciliationController extends AdminController
{
    public function index(
        PaymentReconciliationRunsDataTable $table,
        PaymentReconciliationRunRepositoryInterface $runs,
        GatewayWebhookEventRepositoryInterface $events,
        PaymentRefundRepositoryInterface $refunds,
    ): mixed {
        return $table->render('admin.payment-reconciliation.index', [
            'latest' => $runs->latest(),
            'eventsNeedingAttention' => $events->needingAttentionCount(),
            'refundsAwaitingApproval' => $refunds->awaitingApprovalCount(),
        ]);
    }

    public function show(PaymentReconciliationRun $paymentReconciliation): View
    {
        return view('admin.payment-reconciliation.show', ['run' => $paymentReconciliation]);
    }

    public function run(RunPaymentReconciliationRequest $request, PaymentReconciliationService $reconciliation): AdminFormResponse
    {
        $run = $reconciliation->run($this->actor());

        return $this->formResponse($run->findings === null ? __('admin.payment_reconciliation.run_ok') : __('admin.payment_reconciliation.run_mismatch'))
            ->redirect(route('admin.payment-reconciliation.show', $run));
    }
}
