<?php

namespace App\Console\Commands;

use App\Services\Support\TicketStaffService;
use Illuminate\Console\Command;

/**
 * `support:maintain` (every five minutes): flags tickets whose first response is overdue (SLA) and closes tickets that stayed
 * resolved long enough. Idempotent.
 */
class MaintainSupportTickets extends Command
{
    protected $signature = 'support:maintain';

    protected $description = 'Flag SLA breaches and auto-close resolved tickets';

    public function handle(TicketStaffService $tickets): int
    {
        $this->info($tickets->flagSlaBreaches().' SLA breaches flagged, '.$tickets->autoCloseResolved().' tickets closed.');

        return self::SUCCESS;
    }
}
