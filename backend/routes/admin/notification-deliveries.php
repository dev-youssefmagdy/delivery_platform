<?php

use App\Http\Controllers\Admin\Notification\NotificationDeliveryController;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Route;

/*
| Notification delivery log (notifications.manage). Loaded by routes/admin.php.
*/
Route::prefix('notification-deliveries')->name('notification-deliveries.')->controller(NotificationDeliveryController::class)->group(function (): void {
    Route::get('/', 'index')->name('index')->can('viewAny', NotificationDelivery::class);
    Route::get('{notificationDelivery}', 'show')->whereUuid('notificationDelivery')->name('show')->can('view', 'notificationDelivery');
    Route::post('{notificationDelivery}/retry', 'retry')->whereUuid('notificationDelivery')->name('retry')->can('retry', 'notificationDelivery');
});
