<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Reviews\RatingReportStatus;
use App\Models\RatingReport;
use Illuminate\Contracts\Auth\Access\Authorizable;

class RatingReportPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ReviewsView->value);
    }

    public function dismiss(Authorizable $admin, RatingReport $report): bool
    {
        return $admin->can(AdminPermission::ReviewsModerate->value) && $report->status === RatingReportStatus::Open;
    }
}
