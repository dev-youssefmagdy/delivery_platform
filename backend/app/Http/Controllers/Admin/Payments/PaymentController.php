<?php

namespace App\Http\Controllers\Admin\Payments;

use App\DataTables\Admin\Payments\BookingPaymentsDataTable;
use App\DataTables\Admin\Payments\CustomerPaymentsDataTable;
use App\DataTables\Admin\Payments\PaymentRefundsTabDataTable;
use App\DataTables\Admin\Payments\PaymentsDataTable;
use App\Exceptions\ServiceUnavailableException;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Payments\RequestRefundRequest;
use App\Http\Requests\Admin\Payments\SyncPaymentRequest;
use App\Integrations\Payments\Exceptions\PaymentGatewayException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\Contracts\Payments\PaymentEventRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRefundRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Payments\PaymentStatusSyncService;
use App\Services\Payments\RefundService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Payments (payments.view): the listing, one payment with its timeline, refunds and activity, and the two money actions:
 * ask for a refund (refunds.request, mandatory reason, dual approval above the threshold) and re-check with the gateway
 * (finance.reconcile, mandatory reason). Nothing here edits a payment's state directly.
 */
final class PaymentController extends AdminController
{
    public function index(PaymentsDataTable $table): mixed
    {
        return $table->render('admin.payments.index');
    }

    public function show(Payment $payment, PaymentEventRepositoryInterface $events, PaymentRefundRepositoryInterface $refunds): View
    {
        $payment->load(['booking', 'user', 'paymentMethod']);

        return view('admin.payments.show', [
            'payment' => $payment,
            'events' => $events->forPayment($payment->id),
            'refundableMinor' => max(0, $payment->amount_minor - $refunds->reservedMinor($payment->id)),
        ]);
    }

    public function refundsTab(PaymentRefundsTabDataTable $table, Payment $payment): mixed
    {
        return $table->render('admin.payments.tabs.table');
    }

    public function activity(Payment $payment, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($payment)]);
    }

    public function bookingTab(BookingPaymentsDataTable $table, Booking $booking): mixed
    {
        return $table->render('admin.payments.tabs.table');
    }

    public function customerTab(CustomerPaymentsDataTable $table, User $customer): mixed
    {
        return $table->render('admin.payments.tabs.table');
    }

    public function requestRefund(RequestRefundRequest $request, Payment $payment, RefundService $refunds): AdminFormResponse
    {
        $refund = $refunds->request($this->actor(), $payment, $request->toDto());

        return $this->formResponse(__($refund->requires_dual_approval ? 'admin.refunds.requested_pending' : 'admin.refunds.requested_sent'))
            ->redirect(route('admin.payment-refunds.show', $refund));
    }

    public function sync(SyncPaymentRequest $request, Payment $payment, PaymentStatusSyncService $sync): AdminFormResponse
    {
        try {
            $sync->refreshForAdmin($payment, $this->actor(), (string) $request->reason());
        } catch (PaymentGatewayException) {
            throw ServiceUnavailableException::make('payment_gateway_unavailable');
        }

        return $this->formResponse(__('admin.payments.synced'))->reload();
    }
}
