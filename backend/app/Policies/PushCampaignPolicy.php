<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\PushCampaign;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Push campaigns (push_campaigns.manage): marketing messages to real people, so every state change is checked against the
 * campaign's current status as well as the permission.
 */
class PushCampaignPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, PushCampaign $campaign): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, PushCampaign $campaign): bool
    {
        return $this->allowed($admin) && $campaign->status->isEditable();
    }

    public function schedule(Authorizable $admin, PushCampaign $campaign): bool
    {
        return $this->allowed($admin) && $campaign->status->value === 'draft' && $campaign->scheduled_at !== null;
    }

    public function send(Authorizable $admin, PushCampaign $campaign): bool
    {
        return $this->allowed($admin) && $campaign->status->isEditable();
    }

    public function cancel(Authorizable $admin, PushCampaign $campaign): bool
    {
        return $this->allowed($admin) && $campaign->status->isEditable();
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PushCampaignsManage->value);
    }
}
