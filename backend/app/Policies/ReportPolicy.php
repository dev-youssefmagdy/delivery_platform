<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Reporting\ReportKey;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Reports (SRS 16.14). Every report needs `reports.view` AND the permission of the data it reads (finance, support, safety...), so the
 * reports hub never opens data the admin cannot see in its own module. Exports need `reports.export`; personal-data columns
 * need the elevated `reports.export_pii`.
 */
class ReportPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ReportsView->value);
    }

    public function view(Authorizable $admin, ReportKey $report): bool
    {
        return $admin->can(AdminPermission::ReportsView->value) && $admin->can($report->permission()->value);
    }

    public function export(Authorizable $admin, ReportKey $report): bool
    {
        return $this->view($admin, $report) && ! $report->isPlaceholder() && $admin->can(AdminPermission::ReportsExport->value);
    }

    public function viewPii(Authorizable $admin, ReportKey $report): bool
    {
        return $this->view($admin, $report) && $admin->can(AdminPermission::ReportsExportPii->value);
    }
}
