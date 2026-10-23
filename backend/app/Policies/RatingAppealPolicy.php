<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Reviews\AppealStatus;
use App\Models\RatingAppeal;
use Illuminate\Contracts\Auth\Access\Authorizable;

class RatingAppealPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ReviewsView->value);
    }

    public function decide(Authorizable $admin, RatingAppeal $appeal): bool
    {
        return $admin->can(AdminPermission::ReviewsModerate->value) && $appeal->status === AppealStatus::Open;
    }
}
