<?php

namespace App\DTOs\Booking;

use Carbon\CarbonImmutable;

/**
 * A partial change of a booking that is still searching (PATCH /bookings/{booking}/request). `null` / `$has*` false
 * means "keep what the booking has". The service type, booking type and payment method cannot be changed this way.
 */
final readonly class UpdateBookingRequestData
{
    /**
     * @param list<BookingLocationData> $stops
     * @param array<string, mixed> $serviceDetails
     */
    public function __construct(
        public ?BookingLocationData $pickup = null,
        public ?BookingLocationData $dropoff = null,
        public bool $hasStops = false,
        public array $stops = [],
        public ?CarbonImmutable $scheduledAt = null,
        public ?int $passengers = null,
        public bool $hasServiceDetails = false,
        public array $serviceDetails = [],
        public ?int $proposedFareMinor = null,
        public ?string $currency = null,
    ) {}

    public function changesAnything(): bool
    {
        return $this->pickup !== null || $this->dropoff !== null || $this->hasStops || $this->scheduledAt !== null
            || $this->passengers !== null || $this->hasServiceDetails || $this->proposedFareMinor !== null;
    }
}
