<?php

use App\Http\Controllers\Admin\Demo\DemoItemController;
use App\Http\Middleware\Admin\EnsureDemoModuleEnabled;
use App\Models\DemoItem;
use Illuminate\Support\Facades\Route;

/*
| DemoItem admin routes (make:admin-module). Loaded by routes/admin.php inside the admin group.
| Proof module: registered only in local/testing with ADMIN_DEMO_ENABLED=true, and guarded again at runtime.
*/
if (! EnsureDemoModuleEnabled::enabled()) {
    return;
}

Route::prefix('demo-items')->name('demo-items.')->middleware(EnsureDemoModuleEnabled::class)->controller(DemoItemController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', DemoItem::class);
    Route::get('create', 'create')->name('create')->can('create', DemoItem::class);
    Route::post('/', 'store')->name('store')->can('create', DemoItem::class);
    Route::post('export', 'export')->name('export')->can('export', DemoItem::class);
    Route::get('{demoItem}', 'show')->whereNumber('demoItem')->name('show')->can('view', 'demoItem');
    Route::get('{demoItem}/quick-view', 'quickView')->whereNumber('demoItem')->name('quick-view')->can('view', 'demoItem');
    Route::get('{demoItem}/activity', 'activity')->whereNumber('demoItem')->name('activity')->can('view', 'demoItem');
    Route::get('{demoItem}/edit', 'edit')->whereNumber('demoItem')->name('edit')->can('update', 'demoItem');
    Route::put('{demoItem}', 'update')->whereNumber('demoItem')->name('update')->can('update', 'demoItem');
    Route::post('{demoItem}/archive', 'archive')->whereNumber('demoItem')->name('archive')->can('archive', 'demoItem');
    Route::delete('{demoItem}', 'destroy')->whereNumber('demoItem')->name('destroy')->can('delete', 'demoItem');
});
