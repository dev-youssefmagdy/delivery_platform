<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Payout\PayoutStatus;
use App\Models\Admin;
use App\Models\PayoutRequest;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Payout requests (SRS §12.3): read with `payouts.view`; start the review, run the transfer (processing / paid / failed) and
 * build batches with `payouts.review`; approve, reject and reverse with `payouts.approve`. Above the dual-approval threshold the
 * admin who gave the first approval cannot give the second. Each ability also checks the payout's state, so the UI hides what
 * the service would refuse.
 */
class PayoutRequestPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PayoutsView->value);
    }

    public function view(Authorizable $admin, PayoutRequest $payout): bool
    {
        return $admin->can(AdminPermission::PayoutsView->value);
    }

    public function review(Authorizable $admin, PayoutRequest $payout): bool
    {
        return $admin->can(AdminPermission::PayoutsReview->value) && $payout->status === PayoutStatus::Requested;
    }

    public function approve(Authorizable $admin, PayoutRequest $payout): bool
    {
        return $admin->can(AdminPermission::PayoutsApprove->value)
            && in_array($payout->status, PayoutStatus::awaitingDecision(), true)
            && ! ($payout->requires_dual_approval && $admin instanceof Admin && $payout->first_approved_by_id === $admin->id);
    }

    public function reject(Authorizable $admin, PayoutRequest $payout): bool
    {
        return $admin->can(AdminPermission::PayoutsApprove->value)
            && in_array($payout->status, [...PayoutStatus::awaitingDecision(), PayoutStatus::Approved], true)
            && $payout->payout_batch_id === null;
    }

    public function markProcessing(Authorizable $admin, PayoutRequest $payout): bool
    {
        return $admin->can(AdminPermission::PayoutsReview->value) && $payout->status === PayoutStatus::Approved;
    }

    public function markPaid(Authorizable $admin, PayoutRequest $payout): bool
    {
        return $admin->can(AdminPermission::PayoutsReview->value) && $payout->status === PayoutStatus::Processing;
    }

    public function markFailed(Authorizable $admin, PayoutRequest $payout): bool
    {
        return $this->markPaid($admin, $payout);
    }

    public function reverse(Authorizable $admin, PayoutRequest $payout): bool
    {
        return $admin->can(AdminPermission::PayoutsApprove->value) && $payout->status === PayoutStatus::Paid;
    }

    public function createBatch(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PayoutsReview->value);
    }
}
