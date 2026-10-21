<?php

namespace App\DTOs\Booking;

/**
 * What the customer's booking view says about offers before the offer module (P2.4) exists in detail.
 */
final readonly class OffersSummary
{
    public function __construct(public int $activeCount = 0, public ?int $bestFareMinor = null, public ?int $lowestFareMinor = null) {}

    /**
     * @return array{active_count: int, best_fare_minor: int|null}
     */
    public function toArray(): array
    {
        return ['active_count' => $this->activeCount, 'best_fare_minor' => $this->lowestFareMinor ?? $this->bestFareMinor];
    }
}
