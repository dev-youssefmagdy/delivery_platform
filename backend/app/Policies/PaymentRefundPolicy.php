<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Payments\RefundStatus;
use App\Models\Admin;
use App\Models\PaymentRefund;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Refunds: read like payments; approving or rejecting needs `refunds.approve`, only while the refund waits for approval,
 * and never by the admin who requested it (dual approval, SRS §12).
 */
class PaymentRefundPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->canRead($admin);
    }

    public function view(Authorizable $admin, PaymentRefund $refund): bool
    {
        return $this->canRead($admin);
    }

    public function approve(Authorizable $admin, PaymentRefund $refund): bool
    {
        return $this->decide($admin, $refund);
    }

    public function reject(Authorizable $admin, PaymentRefund $refund): bool
    {
        return $this->decide($admin, $refund);
    }

    private function decide(Authorizable $admin, PaymentRefund $refund): bool
    {
        return $admin->can(AdminPermission::RefundsApprove->value)
            && $refund->status === RefundStatus::PendingApproval
            && ! ($admin instanceof Admin && $admin->id === $refund->requested_by_id);
    }

    private function canRead(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PaymentsView->value)
            || $admin->can(AdminPermission::RefundsRequest->value)
            || $admin->can(AdminPermission::RefundsApprove->value);
    }
}
