<?php

namespace App\Http\Requests\Admin\Notification;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Cancel a push campaign (reason mandatory: true).
 */
class CancelPushCampaignRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'cancel';
    }

    protected function parameter(): string
    {
        return 'pushCampaign';
    }
}
