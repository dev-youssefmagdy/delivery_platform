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
 * Share of the person's bookings of the window that they cancelled themselves (customer) or that the provider cancelled after taking
 * them. Needs a minimum number of bookings so one cancelled trip is never a pattern.
 */
final class CancellationRateDetector implements RiskDetector
{
    public function __construct(private readonly RiskDataRepositoryInterface $data) {}

    public function type(): RiskSignalType
    {
        return RiskSignalType::CancellationRate;
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
        $min = max(1, (int) config('risk.cancellation_rate.min_bookings', 5));
        $threshold = max(1, (int) config('risk.cancellation_rate.min_rate_percent', 50));
        $candidates = [];

        foreach ($this->subjectTypes() as $subject) {
            $status = $subject === RiskSubjectType::Provider ? BookingStatus::CancelledByProvider : BookingStatus::CancelledByCustomer;

            foreach ($this->data->cancellationStats($subject, $since, $min) as $row) {
                $rate = $row['total'] === 0 ? 0 : (int) round($row['cancelled'] * 100 / $row['total']);

                if ($rate < $threshold) {
                    continue;
                }

                $candidates[] = new RiskCandidate($this->type(), $subject, $row['subject_id'], RiskScore::scale($rate, $threshold, 100), [
                    'window_days' => (int) config('risk.window_days', 30), 'bookings' => $row['total'], 'cancelled' => $row['cancelled'],
                    'rate_percent' => $rate, 'threshold_percent' => $threshold, 'min_bookings' => $min,
                    'sample_bookings' => $this->data->sampleBookingCodes($subject, $row['subject_id'], [$status->value], $since),
                ]);
            }
        }

        return $candidates;
    }
}
