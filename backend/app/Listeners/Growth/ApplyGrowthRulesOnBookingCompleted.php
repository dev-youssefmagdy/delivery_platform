<?php

namespace App\Listeners\Growth;

use App\Enums\Booking\BookingStatus;
use App\Events\Booking\BookingStatusTransitioned;
use App\Services\Growth\GrowthCompletionService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * A booking finished: count it for referrals, loyalty and provider incentives (each behind its feature flag). Queued, after the
 * booking transaction committed, so a growth failure can never fail a trip; the service is idempotent per booking.
 */
final class ApplyGrowthRulesOnBookingCompleted implements ShouldQueue
{
    public int $tries = 5;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function __construct(private readonly GrowthCompletionService $growth) {}

    public function handle(BookingStatusTransitioned $event): void
    {
        if ($event->to === BookingStatus::Completed) {
            $this->growth->handle($event->bookingId);
        }
    }
}
