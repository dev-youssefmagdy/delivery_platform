<?php

namespace App\Http\Requests\Admin\Notification;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Activate a notification template (reason mandatory: false).
 */
class ActivateNotificationTemplateRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'activate';
    }

    protected function parameter(): string
    {
        return 'notificationTemplate';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
