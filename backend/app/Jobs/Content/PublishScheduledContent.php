<?php

namespace App\Jobs\Content;

use App\Enums\Common\QueueName;
use App\Services\Content\ContentWorkflowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled every minute: publishes CMS items whose scheduled time has passed. Idempotent and safe to run concurrently
 * (each item is locked and its status re-checked, so nothing is published twice).
 */
class PublishScheduledContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue(QueueName::Default->value);
    }

    public function handle(ContentWorkflowService $workflow): void
    {
        $workflow->publishDue();
    }
}
