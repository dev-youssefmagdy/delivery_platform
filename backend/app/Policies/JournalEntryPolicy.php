<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\JournalEntry;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Journal entries are read-only in the panel (`ledger.view`). Corrections are compensating entries posted by services.
 */
class JournalEntryPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::LedgerView->value);
    }

    public function view(Authorizable $admin, JournalEntry $entry): bool
    {
        return $admin->can(AdminPermission::LedgerView->value);
    }
}
