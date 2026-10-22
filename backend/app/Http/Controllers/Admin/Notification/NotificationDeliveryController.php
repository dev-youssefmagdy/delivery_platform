<?php

namespace App\Http\Controllers\Admin\Notification;

use App\DataTables\Admin\Notification\NotificationDeliveriesDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Notification\RetryNotificationDeliveryRequest;
use App\Models\NotificationDelivery;
use App\Services\Notification\NotificationDeliveryAdminService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Delivery log (notifications.manage): every attempt to reach someone, per channel, with status, attempts and the
 * provider's answer. Failed deliveries can be sent again with a reason.
 */
final class NotificationDeliveryController extends AdminController
{
    public function index(NotificationDeliveriesDataTable $table): mixed
    {
        return $table->render('admin.notification-deliveries.index');
    }

    public function show(NotificationDelivery $notificationDelivery): View
    {
        return view('admin.notification-deliveries.show', ['delivery' => $notificationDelivery->load('notification')]);
    }

    public function retry(RetryNotificationDeliveryRequest $request, NotificationDelivery $notificationDelivery, NotificationDeliveryAdminService $service): AdminFormResponse
    {
        $service->retry($notificationDelivery, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.notification_deliveries.retried'))->reloadTable(NotificationDeliveriesDataTable::TABLE_ID)->reload();
    }
}
