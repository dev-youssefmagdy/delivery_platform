<?php

namespace App\Jobs\Notification;

use App\Enums\Common\QueueName;
use App\Services\Notification\PushCampaignService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends a push campaign to its audience. Idempotent: a repeated run finds every notification already stored (dedupe key)
 * and only refreshes the counters.
 */
class RunPushCampaign implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(public readonly int $campaignId)
    {
        $this->onQueue(QueueName::Notifications->value);
        $this->onConnection(config('queue.long_connection')); // timeout above the default retry_after
    }

    public function handle(PushCampaignService $campaigns): void
    {
        $campaigns->run($this->campaignId);
    }
}
