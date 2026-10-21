<?php

namespace App\DTOs\Booking;

use Carbon\CarbonImmutable;

/**
 * What the provider app reports when it presses "Arrived": its GPS fix, when the fix was taken and how accurate it was.
 * All optional: without a fix the server falls back to the last known position, and without any it stores the arrival
 * as unverified (it never blocks the provider and never triggers a fee on its own).
 */
final readonly class ArrivalData
{
    public function __construct(
        public ?float $lat = null,
        public ?float $lng = null,
        public ?float $accuracyMeters = null,
        public ?CarbonImmutable $recordedAt = null,
    ) {}

    public function hasFix(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }
}
