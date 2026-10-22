<?php

namespace App\Http\Controllers\Admin\Payments;

use App\DataTables\Admin\Payments\RefundsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Payments\ApproveRefundRequest;
use App\Http\Requests\Admin\Payments\RejectRefundRequest;
use App\Models\PaymentRefund;
use App\Services\Audit\AuditTrailService;
use App\Services\Payments\RefundService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Refunds (payments.view / refunds.request / refunds.approve): the listing, one refund and the decision on a refund that
 * waits for a second admin. Rejecting needs a reason; approving sends the refund to the gateway; the ledger is touched only
 * when the gateway confirms.
 */
final class RefundController extends AdminController
{
    public function __construct(private readonly RefundService $refunds) {}

    public function index(RefundsDataTable $table): mixed
    {
        return $table->render('admin.payment-refunds.index');
    }

    public function show(PaymentRefund $paymentRefund, AuditTrailService $trail): View
    {
        $paymentRefund->load(['payment.booking', 'payment.user', 'requestedBy', 'decidedBy']);

        return view('admin.payment-refunds.show', ['refund' => $paymentRefund, 'logs' => $trail->latestFor($paymentRefund)]);
    }

    public function approve(ApproveRefundRequest $request, PaymentRefund $paymentRefund): AdminFormResponse
    {
        $this->refunds->approve($this->actor(), $paymentRefund, $request->reason());

        return $this->formResponse(__('admin.refunds.approved'))->reloadTable(RefundsDataTable::TABLE_ID)->reload();
    }

    public function reject(RejectRefundRequest $request, PaymentRefund $paymentRefund): AdminFormResponse
    {
        $this->refunds->reject($this->actor(), $paymentRefund, (string) $request->reason());

        return $this->formResponse(__('admin.refunds.rejected'))->reloadTable(RefundsDataTable::TABLE_ID)->reload();
    }
}
