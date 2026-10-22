<?php

namespace App\Http\Requests\Admin\Notification;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Deactivate a notification template (reason mandatory: true).
 */
class DeactivateNotificationTemplateRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'deactivate';
    }

    protected function parameter(): string
    {
        return 'notificationTemplate';
    }
}
