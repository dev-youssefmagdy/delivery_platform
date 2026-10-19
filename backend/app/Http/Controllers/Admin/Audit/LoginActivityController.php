<?php

namespace App\Http\Controllers\Admin\Audit;

use App\DataTables\Admin\Audit\LoginActivityDataTable;
use App\Http\Controllers\Admin\AdminController;

/**
 * Read-only login activity (login_activity.view).
 */
final class LoginActivityController extends AdminController
{
    public function index(LoginActivityDataTable $table): mixed
    {
        return $table->render('admin.login-activity.index');
    }
}
