<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Payments\PaymentStatus;
use App\Models\Payment;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Payments: read with `payments.view` (a refund requester or approver can read too, they must see what they decide on);
 * asking for a refund needs `refunds.request`; a manual gateway re-check needs `finance.reconcile`. Payments are never
 * created, edited or deleted from the panel: their state changes only through the gateway (webhook / query) or the cash rule.
 */
class PaymentPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->canRead($admin);
    }

    public function view(Authorizable $admin, Payment $payment): bool
    {
        return $this->canRead($admin);
    }

    public function requestRefund(Authorizable $admin, Payment $payment): bool
    {
        return $admin->can(AdminPermission::RefundsRequest->value)
            && $payment->gateway !== null
            && in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)
            && $payment->amount_minor > $payment->refunded_minor;
    }

    public function sync(Authorizable $admin, Payment $payment): bool
    {
        return $admin->can(AdminPermission::FinanceReconcile->value)
            && $payment->gateway !== null
            && in_array($payment->status, [...PaymentStatus::awaitingGateway(), PaymentStatus::ReconciliationRequired, PaymentStatus::Failed], true);
    }

    private function canRead(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PaymentsView->value)
            || $admin->can(AdminPermission::RefundsRequest->value)
            || $admin->can(AdminPermission::RefundsApprove->value);
    }
}
