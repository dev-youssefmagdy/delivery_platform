<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\PaymentReconciliationRun;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Payment reconciliation report: reading needs `finance.view` or `payments.view`, running a check by hand needs
 * `finance.reconcile`. The run only reports: it never changes a payment or the ledger.
 */
class PaymentReconciliationRunPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->canRead($admin);
    }

    public function view(Authorizable $admin, PaymentReconciliationRun $run): bool
    {
        return $this->canRead($admin);
    }

    public function run(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FinanceReconcile->value);
    }

    private function canRead(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FinanceView->value) || $admin->can(AdminPermission::PaymentsView->value);
    }
}
