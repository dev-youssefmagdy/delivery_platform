<?php

namespace App\Console\Commands;

use App\Services\Risk\RiskSignalEvaluationService;
use Illuminate\Console\Command;

/**
 * `risk:evaluate`: runs the risk detectors now (the scheduler runs the same work hourly through a job). Safe to rerun any time.
 */
class EvaluateRisk extends Command
{
    protected $signature = 'risk:evaluate';

    protected $description = 'Evaluate risk signals (cancellation, no-show, offer spam, location anomaly, account farm, payment dispute)';

    public function handle(RiskSignalEvaluationService $evaluation): int
    {
        $result = $evaluation->run();
        $this->info(sprintf('%d created, %d refreshed, %d skipped (cooldown), %d closed.', $result['created'], $result['refreshed'], $result['skipped'], $result['closed']));

        return self::SUCCESS;
    }
}
