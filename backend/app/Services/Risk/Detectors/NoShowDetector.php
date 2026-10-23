<?php

namespace App\Services\Risk\Detectors;

use App\DTOs\Risk\RiskCandidate;
use App\Enums\Booking\BookingStatus;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use App\Repositories\Contracts\Risk\RiskDataRepositoryInterface;
use App\Services\Risk\RiskScore;
use Carbon\CarbonInterface;

/**
 * Repeated no-shows: a customer the provider cancelled on as a no-show, or a provider the customer cancelled on as a no-show.
 */
final class NoShowDetector implements RiskDetector
{
    public function __construct(private readonly RiskDataRepositoryInterface $data) {}

    public function type(): RiskSignalType
    {
        return RiskSignalType::NoShow;
    }

    public function subjectTypes(): array
    {
        return [RiskSubjectType::Customer, RiskSubjectType::Provider];
    }

    public function enabled(): bool
    {
        return true;
    }

    public function detect(CarbonInterface $since): array
    {
        $min = max(1, (int) config('risk.no_show.min_events', 3));
        $candidates = [];

        foreach ($this->subjectTypes() as $subject) {
            $status = $subject === RiskSubjectType::Provider ? BookingStatus::CancelledByCustomer : BookingStatus::CancelledByProvider;

            foreach ($this->data->noShowCounts($subject, $since, $min) as $row) {
                $candidates[] = new RiskCandidate($this->type(), $subject, $row['subject_id'], RiskScore::scale($row['events'], $min, $min * 4), [
                    'window_days' => (int) config('risk.window_days', 30), 'no_shows' => $row['events'], 'min_events' => $min,
                    'sample_bookings' => $this->data->sampleBookingCodes($subject, $row['subject_id'], [$status->value], $since),
                ]);
            }
        }

        return $candidates;
    }
}
