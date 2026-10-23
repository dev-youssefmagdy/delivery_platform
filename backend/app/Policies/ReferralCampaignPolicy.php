<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Growth\CampaignStatus;
use App\Models\ReferralCampaign;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Referral campaigns (referrals.view / referrals.manage). Editing needs a draft or paused campaign; a transition needs a legal target.
 */
class ReferralCampaignPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ReferralsView->value);
    }

    public function view(Authorizable $admin, ReferralCampaign $campaign): bool
    {
        return $admin->can(AdminPermission::ReferralsView->value);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ReferralsManage->value);
    }

    public function update(Authorizable $admin, ReferralCampaign $campaign): bool
    {
        return $admin->can(AdminPermission::ReferralsManage->value) && $campaign->status->isEditable();
    }

    public function transition(Authorizable $admin, ReferralCampaign $campaign, ?CampaignStatus $target = null): bool
    {
        return $admin->can(AdminPermission::ReferralsManage->value)
            && ($target === null ? $campaign->status->next() !== [] : $campaign->status->canMoveTo($target));
    }

    public function adjust(Authorizable $admin, ReferralCampaign $campaign): bool
    {
        return $admin->can(AdminPermission::ReferralsManage->value) && $campaign->status !== CampaignStatus::Archived;
    }
}
