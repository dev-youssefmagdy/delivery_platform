<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Finance\CashReconciliationStatus;
use App\Models\CashReconciliation;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Cash reconciliation: read with `finance.view`; open, settle and cancel with `finance.reconcile` (settling and cancelling only
 * while the reconciliation is open).
 */
class CashReconciliationPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FinanceView->value) || $admin->can(AdminPermission::FinanceReconcile->value);
    }

    public function view(Authorizable $admin, CashReconciliation $reconciliation): bool
    {
        return $this->viewAny($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FinanceReconcile->value);
    }

    public function settle(Authorizable $admin, CashReconciliation $reconciliation): bool
    {
        return $admin->can(AdminPermission::FinanceReconcile->value) && $reconciliation->status === CashReconciliationStatus::Open;
    }

    public function cancel(Authorizable $admin, CashReconciliation $reconciliation): bool
    {
        return $this->settle($admin, $reconciliation);
    }
}
