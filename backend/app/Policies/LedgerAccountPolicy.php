<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\LedgerAccount;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Ledger accounts are read-only in the panel. The accounts listing and page need `ledger.view`; the wallet tabs on
 * customer and provider pages need `finance.view` or `wallets.view` (the wallet of one person, not the whole ledger).
 */
class LedgerAccountPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::LedgerView->value);
    }

    public function view(Authorizable $admin, LedgerAccount $account): bool
    {
        return $admin->can(AdminPermission::LedgerView->value);
    }

    public function viewWallets(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FinanceView->value) || $admin->can(AdminPermission::WalletsView->value);
    }

    /**
     * Provider statement export: a finance read (`finance.view`) plus the export right (`reports.export`); every export is audited.
     */
    public function exportStatements(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::FinanceView->value) && $admin->can(AdminPermission::ReportsExport->value);
    }
}
