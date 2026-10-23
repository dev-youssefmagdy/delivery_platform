<?php

namespace App\Services\Risk\Detectors;

use App\DTOs\Risk\RiskCandidate;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use Carbon\CarbonInterface;

/**
 * One scheduled check of the risk module. It only reads data and returns candidates with their explanation; storing, refreshing and
 * closing signals belongs to RiskSignalEvaluationService, so every detector is idempotent by construction.
 */
interface RiskDetector
{
    public function type(): RiskSignalType;

    /**
     * @return list<RiskSubjectType> the subject types this detector can flag (cleared signals are closed per subject type)
     */
    public function subjectTypes(): array;

    /**
     * A detector that waits for its data source (referral abuse until P5.7) is disabled: it neither raises nor closes anything.
     */
    public function enabled(): bool;

    /**
     * @return list<RiskCandidate>
     */
    public function detect(CarbonInterface $since): array;
}
