<?php

namespace App\Console\Commands;

use App\Services\Safety\IncidentStaffService;
use Illuminate\Console\Command;

/**
 * `safety:maintain` (every minute): flags new cases whose acknowledgement is overdue (SLA) and, once a day's worth of work is found,
 * removes the evidence of cases resolved longer ago than the retention period (never under a legal hold). Idempotent.
 */
class MaintainSafetyIncidents extends Command
{
    protected $signature = 'safety:maintain';

    protected $description = 'Flag overdue safety cases and purge expired evidence';

    public function handle(IncidentStaffService $incidents): int
    {
        $this->info($incidents->flagSlaBreaches().' SLA breaches flagged, '.$incidents->purgeExpired().' cases purged.');

        return self::SUCCESS;
    }
}
