<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Growth\IncentiveProgressStatus;
use App\Models\IncentiveProgress;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Quest progress (incentives.view); deciding a held one needs incentives.review and a held row.
 */
class IncentiveProgressPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::IncentivesView->value);
    }

    public function review(Authorizable $admin, IncentiveProgress $progress): bool
    {
        return $admin->can(AdminPermission::IncentivesReview->value) && $progress->status === IncentiveProgressStatus::Held;
    }
}
