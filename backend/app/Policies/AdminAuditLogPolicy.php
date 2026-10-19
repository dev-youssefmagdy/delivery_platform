<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\AdminAuditLog;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Read-only audit trail (audit_logs.view). There are no write abilities: entries are append-only.
 */
class AdminAuditLogPolicy
{
    public function viewAny(Authorizable $actor): bool
    {
        return $actor->can(AdminPermission::AuditLogsView->value);
    }

    public function view(Authorizable $actor, AdminAuditLog $log): bool
    {
        return $actor->can(AdminPermission::AuditLogsView->value);
    }
}
