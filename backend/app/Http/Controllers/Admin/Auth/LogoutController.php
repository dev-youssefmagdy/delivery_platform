<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Admin;
use App\Services\Admin\AdminAuthService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Ends the current admin session. Signing out twice (or as a guest) just lands on the login page.
 */
final class LogoutController extends AdminController
{
    public function __construct(private readonly AdminAuthService $auth) {}

    public function __invoke(Request $request): RedirectResponse|AdminFormResponse
    {
        $admin = auth('admin')->user();

        if ($admin instanceof Admin) {
            $this->auth->logout($admin);
        }

        return $request->expectsJson()
            ? $this->formResponse()->redirect(route('admin.login'))
            : redirect()->route('admin.login');
    }
}
