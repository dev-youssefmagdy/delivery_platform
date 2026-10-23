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
 * Disputed bookings (both sides) plus refunds and failed payments (customers) piling up inside the window.
 */
final class PaymentDisputeDetector implements RiskDetector
{
    public function __construct(private readonly RiskDataRepositoryInterface $data) {}

    public function type(): RiskSignalType
    {
        return RiskSignalType::PaymentDispute;
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
        $min = max(1, (int) config('risk.payment_dispute.min_events', 3));
        $candidates = [];

        foreach ($this->subjectTypes() as $subject) {
            foreach ($this->data->paymentDisputeCounts($subject, $since, $min) as $row) {
                $total = $row['disputed'] + $row['refunds'] + $row['failed_payments'];
                $candidates[] = new RiskCandidate($this->type(), $subject, $row['subject_id'], RiskScore::scale($total, $min, $min * 4), [
                    'window_days' => (int) config('risk.window_days', 30), 'disputed_bookings' => $row['disputed'], 'refunds' => $row['refunds'],
                    'failed_payments' => $row['failed_payments'], 'events' => $total, 'min_events' => $min,
                    'sample_bookings' => $this->data->sampleBookingCodes($subject, $row['subject_id'], [BookingStatus::Disputed->value], $since),
                ]);
            }
        }

        return $candidates;
    }
}
