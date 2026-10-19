<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Admin\AdminPermission;
use App\Support\Admin\AdminAccess;
use App\Support\Admin\AdminMenu;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Admin entry point: the dashboard when permitted, otherwise the first menu entry the admin can see.
 */
final class HomeController extends AdminController
{
    public function __invoke(AdminMenu $menu): RedirectResponse|View
    {
        if (AdminAccess::allows(AdminPermission::DashboardView->value)) {
            return redirect()->route('admin.dashboard');
        }

        $url = $menu->firstUrl();

        return $url !== null ? redirect()->to($url) : view('admin.home');
    }
}
