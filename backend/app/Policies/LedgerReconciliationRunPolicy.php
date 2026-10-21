<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\LedgerReconciliationRun;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Reconciliation report: reading needs `finance.view`, running a check by hand needs `finance.reconcile`. Neither can
 * change the ledger: the run only reports.
 */
class LedgerReconciliationRunPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FinanceView->value);
    }

    public function view(Authorizable $admin, LedgerReconciliationRun $run): bool
    {
        return $admin->can(AdminPermission::FinanceView->value);
    }

    public function run(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FinanceReconcile->value);
    }
}
