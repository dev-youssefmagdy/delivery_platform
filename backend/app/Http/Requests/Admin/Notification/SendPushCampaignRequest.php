<?php

namespace App\Http\Requests\Admin\Notification;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Send a push campaign (reason mandatory: true).
 */
class SendPushCampaignRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'send';
    }

    protected function parameter(): string
    {
        return 'pushCampaign';
    }
}
