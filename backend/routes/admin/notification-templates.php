<?php

use App\Http\Controllers\Admin\Notification\NotificationTemplateController;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Route;

/*
| Notification templates (notifications.manage): text per event, channel and language with an ar/en preview.
| Loaded by routes/admin.php.
*/
Route::prefix('notification-templates')->name('notification-templates.')->controller(NotificationTemplateController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', NotificationTemplate::class);
    Route::get('create', 'create')->name('create')->can('create', NotificationTemplate::class);
    Route::post('/', 'store')->name('store')->can('create', NotificationTemplate::class);
    Route::post('preview', 'preview')->name('preview')->can('viewAny', NotificationTemplate::class);
    Route::get('{notificationTemplate}', 'show')->whereNumber('notificationTemplate')->name('show')->can('view', 'notificationTemplate');
    Route::get('{notificationTemplate}/activity', 'activity')->whereNumber('notificationTemplate')->name('activity')->can('view', 'notificationTemplate');
    Route::get('{notificationTemplate}/edit', 'edit')->whereNumber('notificationTemplate')->name('edit')->can('update', 'notificationTemplate');
    Route::put('{notificationTemplate}', 'update')->whereNumber('notificationTemplate')->name('update')->can('update', 'notificationTemplate');
    Route::post('{notificationTemplate}/activate', 'activate')->whereNumber('notificationTemplate')->name('activate')->can('activate', 'notificationTemplate');
    Route::post('{notificationTemplate}/deactivate', 'deactivate')->whereNumber('notificationTemplate')->name('deactivate')->can('deactivate', 'notificationTemplate');
});
