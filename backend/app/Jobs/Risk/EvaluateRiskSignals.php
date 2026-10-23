<?php

namespace App\Jobs\Risk;

use App\Enums\Common\QueueName;
use App\Services\Risk\RiskSignalEvaluationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled hourly: runs every risk detector and refreshes the signal queue. Idempotent (one active signal per type and subject,
 * cooldown after a decision), so a rerun, a retry or two overlapping runs never duplicate a signal. Creates review tasks only.
 */
class EvaluateRiskSignals implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue(QueueName::Default->value);
    }

    public function handle(RiskSignalEvaluationService $evaluation): void
    {
        $evaluation->run();
    }
}
