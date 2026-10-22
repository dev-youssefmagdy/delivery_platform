<?php

namespace App\Http\Requests\Admin\Notification;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Schedule a push campaign (reason mandatory: false).
 */
class SchedulePushCampaignRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'schedule';
    }

    protected function parameter(): string
    {
        return 'pushCampaign';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
