<?php

namespace App\Services\Booking;

use App\DTOs\Booking\CancellationDecision;
use App\DTOs\Booking\CancellationPreviewData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CancellationStage;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use Carbon\CarbonImmutable;

/**
 * The "try it" calculator of a cancellation policy (P3.5): builds an unsaved booking in the chosen scenario and asks the
 * very `CancellationFeeCalculator` that real cancellations use, so the admin sees exactly what the policy would charge.
 * Nothing is written. Waiting is priced like a waiting adjustment: whole minutes past the free wait x the per-minute rate.
 */
final class CancellationPolicyPreviewService
{
    public function __construct(private readonly CancellationFeeCalculator $calculator) {}

    /**
     * @return array{decision: CancellationDecision, waiting_minutes: int, waiting_charge_minor: int}
     */
    public function preview(CancellationPolicy $policy, CancellationPreviewData $data): array
    {
        $now = CarbonImmutable::now();
        $selected = $now->subMinutes($data->minutesSinceSelection);
        $arrivedAt = $data->stage === CancellationStage::Arrived ? $now->subMinutes(min($data->minutesSinceArrival, $data->minutesSinceSelection)) : null;
        $status = match ($data->stage) {
            CancellationStage::ProviderSelected => BookingStatus::ProviderSelected,
            CancellationStage::HeadingToPickup => BookingStatus::ProviderHeadingToPickup,
            default => BookingStatus::ProviderArrived,
        };

        $booking = new Booking;
        $booking->forceFill([
            'status' => $status,
            'currency' => $policy->snapshot()['currency'] ?? $data->currency,
            'agreed_fare_minor' => $data->fareMinor,
            'policy_snapshot' => ['cancellation' => $policy->snapshot()],
            'provider_selected_at' => $selected,
            'arrived_at' => $arrivedAt,
            'free_wait_ends_at' => $arrivedAt?->addSeconds($policy->free_wait_seconds),
            'arrival_evidence' => $arrivedAt === null ? null : ['verified' => $data->arrivalVerified],
        ]);

        $evidence = ['verified' => $data->reportVerified];
        $decision = $this->calculator->decide($booking, $data->actor, $now, $data->reason, $evidence);
        $waitedSeconds = $arrivedAt === null ? 0 : max(0, $policy->free_wait_seconds === 0 ? (int) $arrivedAt->diffInSeconds($now) : (int) $arrivedAt->diffInSeconds($now) - $policy->free_wait_seconds);
        $minutes = (int) ceil($waitedSeconds / 60);

        return ['decision' => $decision, 'waiting_minutes' => $minutes, 'waiting_charge_minor' => $minutes * $policy->waiting_rate_minor];
    }
}
