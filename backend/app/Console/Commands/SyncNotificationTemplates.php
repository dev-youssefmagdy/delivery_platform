<?php

namespace App\Console\Commands;

use App\Services\Notification\TemplateSeedingService;
use Illuminate\Console\Command;

class SyncNotificationTemplates extends Command
{
    protected $signature = 'notifications:sync-templates';

    protected $description = 'Create the notification templates that are missing from the built-in texts (existing ones are kept)';

    public function handle(TemplateSeedingService $templates): int
    {
        $this->info($templates->sync().' templates created.');

        return self::SUCCESS;
    }
}
