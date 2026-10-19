<?php

use App\Http\Controllers\Admin\Roles\RoleController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

/*
| Roles and the permission matrix (roles.view / roles.manage). Loaded by routes/admin.php inside the
| authenticated admin group.
*/
Route::prefix('roles')->name('roles.')->controller(RoleController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', Role::class);
    Route::get('create', 'create')->name('create')->can('create', Role::class);
    Route::post('/', 'store')->name('store')->can('create', Role::class);
    Route::get('{role}', 'show')->whereNumber('role')->name('show')->can('view', 'role');
    Route::get('{role}/activity', 'activity')->whereNumber('role')->name('activity')->can('view', 'role');
    Route::get('{role}/edit', 'edit')->whereNumber('role')->name('edit')->can('update', 'role');
    Route::put('{role}', 'update')->whereNumber('role')->name('update')->can('update', 'role');
    Route::delete('{role}', 'destroy')->whereNumber('role')->name('destroy')->can('delete', 'role');
});
