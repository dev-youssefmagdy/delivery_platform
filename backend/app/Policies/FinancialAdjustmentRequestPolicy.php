<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Finance\ApprovalStatus;
use App\Models\Admin;
use App\Models\FinancialAdjustmentRequest;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Adjustment requests (maker-checker, SRS §12.3): read with `wallets.view`, ask with `wallets.adjust`, decide with
 * `wallets.approve_adjustment` and never on your own request. The requester may cancel their pending request.
 */
class FinancialAdjustmentRequestPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::WalletsView->value) || $admin->can(AdminPermission::WalletsAdjust->value) || $admin->can(AdminPermission::WalletsApproveAdjustment->value);
    }

    public function view(Authorizable $admin, FinancialAdjustmentRequest $request): bool
    {
        return $this->viewAny($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::WalletsAdjust->value);
    }

    public function approve(Authorizable $admin, FinancialAdjustmentRequest $request): bool
    {
        return $this->decide($admin, $request);
    }

    public function reject(Authorizable $admin, FinancialAdjustmentRequest $request): bool
    {
        return $this->decide($admin, $request);
    }

    public function cancel(Authorizable $admin, FinancialAdjustmentRequest $request): bool
    {
        return $admin instanceof Admin
            && $request->status === ApprovalStatus::Pending
            && $request->requested_by_id === $admin->id
            && $admin->can(AdminPermission::WalletsAdjust->value);
    }

    private function decide(Authorizable $admin, FinancialAdjustmentRequest $request): bool
    {
        return $admin instanceof Admin
            && $request->status === ApprovalStatus::Pending
            && $request->requested_by_id !== $admin->id
            && $admin->can(AdminPermission::WalletsApproveAdjustment->value);
    }
}
