<?php

namespace App\DTOs\Operations;

/**
 * Conversion numbers of the Offers monitor for the filtered period: how many offers were submitted, how many were
 * chosen and how many are flagged.
 */
final readonly class OfferStats
{
    public function __construct(
        public int $submitted,
        public int $selected,
        public int $expired,
        public int $withdrawn,
        public int $flagged,
        public int $bookingsWithOffers,
        public int $bookingsTotal,
    ) {}

    /**
     * Selected offers as a share of submitted offers, in percent with one decimal.
     */
    public function conversionPercent(): string
    {
        return $this->submitted === 0 ? '0.0' : number_format($this->selected * 100 / $this->submitted, 1, '.', '');
    }

    /**
     * Bookings that received at least one offer as a share of the bookings created in the period.
     */
    public function responsePercent(): string
    {
        return $this->bookingsTotal === 0 ? '0.0' : number_format($this->bookingsWithOffers * 100 / $this->bookingsTotal, 1, '.', '');
    }
}
