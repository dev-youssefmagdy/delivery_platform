<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\DashboardService;
use Illuminate\Contracts\View\View;

/**
 * Dashboard placeholder: shows only what the admin may reach; real KPIs arrive with P5.3.
 */
final class DashboardController extends AdminController
{
    public function __invoke(DashboardService $dashboard): View
    {
        return view('admin.dashboard', ['overview' => $dashboard->overview($this->actor())]);
    }
}
