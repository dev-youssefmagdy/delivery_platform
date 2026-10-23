<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Lead\LeadStatus;
use App\Models\Lead;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Onboarding leads (leads.view / leads.manage). Moving a lead needs `leads.manage` and a legal target status.
 */
class LeadPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::LeadsView->value);
    }

    public function view(Authorizable $admin, Lead $lead): bool
    {
        return $admin->can(AdminPermission::LeadsView->value);
    }

    public function update(Authorizable $admin, Lead $lead): bool
    {
        return $admin->can(AdminPermission::LeadsManage->value);
    }

    public function transition(Authorizable $admin, Lead $lead, ?LeadStatus $target = null): bool
    {
        return $admin->can(AdminPermission::LeadsManage->value)
            && ($target === null ? $lead->status->next() !== [] : $lead->status->canMoveTo($target));
    }
}
