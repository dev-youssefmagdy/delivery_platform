<?php

namespace App\Http\Requests\Admin\Notification;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Send a failed delivery again (reason mandatory: true).
 */
class RetryNotificationDeliveryRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'retry';
    }

    protected function parameter(): string
    {
        return 'notificationDelivery';
    }
}
