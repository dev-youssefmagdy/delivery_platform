<?php

use App\Http\Controllers\Admin\Admins\AdminUserController;
use App\Models\Admin;
use Illuminate\Support\Facades\Route;

/*
| Admin users (admins.view / admins.manage). Loaded by routes/admin.php inside the authenticated admin group.
*/
Route::prefix('admins')->name('admins.')->controller(AdminUserController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', Admin::class);
    Route::get('create', 'create')->name('create')->can('create', Admin::class);
    Route::post('/', 'store')->name('store')->can('create', Admin::class);
    Route::get('{admin}', 'show')->whereNumber('admin')->name('show')->can('view', 'admin');
    Route::get('{admin}/activity', 'activity')->whereNumber('admin')->name('activity')->can('view', 'admin');
    Route::get('{admin}/edit', 'edit')->whereNumber('admin')->name('edit')->can('update', 'admin');
    Route::put('{admin}', 'update')->whereNumber('admin')->name('update')->can('update', 'admin');
    Route::post('{admin}/suspend', 'suspend')->whereNumber('admin')->name('suspend')->can('suspend', 'admin');
    Route::post('{admin}/reactivate', 'reactivate')->whereNumber('admin')->name('reactivate')->can('reactivate', 'admin');
    Route::post('{admin}/reset-mfa', 'resetMfa')->whereNumber('admin')->name('reset-mfa')->can('resetMfa', 'admin');
    Route::delete('{admin}', 'destroy')->whereNumber('admin')->name('destroy')->can('delete', 'admin');
});
