<?php

namespace App\Http\Controllers\Admin;

use App\Support\Admin\AdminMenu;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Admin entry point: goes to the first menu entry the admin can see (the dashboard arrives in P5.3).
 */
final class HomeController extends AdminController
{
    public function __invoke(AdminMenu $menu): RedirectResponse|View
    {
        $url = $menu->firstUrl();

        return $url !== null ? redirect()->to($url) : view('admin.home');
    }
}
