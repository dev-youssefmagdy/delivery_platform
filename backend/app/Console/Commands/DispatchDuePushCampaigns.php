<?php

namespace App\Console\Commands;

use App\Services\Notification\PushCampaignService;
use Illuminate\Console\Command;

/**
 * `push-campaigns:dispatch-due` (every minute): starts scheduled push campaigns whose time has come. Idempotent.
 */
class DispatchDuePushCampaigns extends Command
{
    protected $signature = 'push-campaigns:dispatch-due';

    protected $description = 'Start scheduled push campaigns that are due';

    public function handle(PushCampaignService $campaigns): int
    {
        $this->info($campaigns->startDue().' campaigns started.');

        return self::SUCCESS;
    }
}
