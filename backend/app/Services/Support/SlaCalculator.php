<?php

namespace App\Services\Support;

use App\Enums\Support\TicketPriority;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * First-response deadline of a ticket from its priority (`support.sla_minutes`, SRS §14.3).
 */
final class SlaCalculator
{
    public function dueAt(TicketPriority $priority, ?CarbonInterface $from = null): CarbonImmutable
    {
        return CarbonImmutable::instance($from ?? now())->addMinutes($priority->slaMinutes());
    }
}
