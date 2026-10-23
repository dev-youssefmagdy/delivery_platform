<?php

namespace App\Console\Commands;

use App\Services\Content\ContentWorkflowService;
use Illuminate\Console\Command;

/**
 * `content:publish-scheduled`: runs the scheduled publish now (the scheduler runs the same logic every minute).
 */
class PublishScheduledContentCommand extends Command
{
    protected $signature = 'content:publish-scheduled';

    protected $description = 'Publish CMS content whose scheduled time has passed (idempotent)';

    public function handle(ContentWorkflowService $workflow): int
    {
        $this->info(sprintf('Published: %d', $workflow->publishDue()));

        return self::SUCCESS;
    }
}
