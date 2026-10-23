<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Growth\CampaignStatus;
use App\Models\IncentiveCampaign;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Provider incentive campaigns (incentives.view / incentives.manage).
 */
class IncentiveCampaignPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::IncentivesView->value);
    }

    public function view(Authorizable $admin, IncentiveCampaign $campaign): bool
    {
        return $admin->can(AdminPermission::IncentivesView->value);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::IncentivesManage->value);
    }

    public function update(Authorizable $admin, IncentiveCampaign $campaign): bool
    {
        return $admin->can(AdminPermission::IncentivesManage->value) && $campaign->status->isEditable();
    }

    public function transition(Authorizable $admin, IncentiveCampaign $campaign, ?CampaignStatus $target = null): bool
    {
        return $admin->can(AdminPermission::IncentivesManage->value)
            && ($target === null ? $campaign->status->next() !== [] : $campaign->status->canMoveTo($target));
    }

    public function adjust(Authorizable $admin, IncentiveCampaign $campaign): bool
    {
        return $admin->can(AdminPermission::IncentivesManage->value) && $campaign->status !== CampaignStatus::Archived;
    }
}
