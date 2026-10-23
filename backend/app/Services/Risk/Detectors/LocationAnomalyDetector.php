<?php

namespace App\Services\Risk\Detectors;

use App\DTOs\Risk\RiskCandidate;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use App\Repositories\Contracts\Risk\RiskDataRepositoryInterface;
use App\Services\Risk\RiskScore;
use Carbon\CarbonInterface;

/**
 * Providers whose location samples were flagged by the ingest checks (impossible speed, future timestamps ...) several times.
 */
final class LocationAnomalyDetector implements RiskDetector
{
    public function __construct(private readonly RiskDataRepositoryInterface $data) {}

    public function type(): RiskSignalType
    {
        return RiskSignalType::LocationAnomaly;
    }

    public function subjectTypes(): array
    {
        return [RiskSubjectType::Provider];
    }

    public function enabled(): bool
    {
        return true;
    }

    public function detect(CarbonInterface $since): array
    {
        $min = max(1, (int) config('risk.location_anomaly.min_events', 5));
        $candidates = [];

        foreach ($this->data->locationAnomalies($since, $min) as $row) {
            $candidates[] = new RiskCandidate($this->type(), RiskSubjectType::Provider, $row['subject_id'], RiskScore::scale($row['events'], $min, $min * 6), [
                'window_days' => (int) config('risk.window_days', 30), 'anomalies' => $row['events'], 'by_type' => $row['by_type'], 'min_events' => $min,
            ]);
        }

        return $candidates;
    }
}
