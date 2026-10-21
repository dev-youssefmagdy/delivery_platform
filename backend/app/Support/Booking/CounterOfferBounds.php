<?php

namespace App\Support\Booking;

use App\Models\Booking;

/**
 * The counter-offer range of a booking, read from the policy snapshot taken at creation (so admin edits never change
 * a running negotiation, Plan I4). Percentages are of the customer's proposed fare; the arithmetic is integer only:
 * the lowest allowed amount rounds up, the highest rounds down, so the range never widens by rounding.
 */
final class CounterOfferBounds
{
    /**
     * @return array{min: int|null, max: int|null} minor units; null = no limit on that side
     */
    public static function for(Booking $booking): array
    {
        $minPct = $booking->policy_snapshot['offers']['counter_offer_min_pct'] ?? null;
        $maxPct = $booking->policy_snapshot['offers']['counter_offer_max_pct'] ?? null;

        return [
            'min' => $minPct === null ? null : intdiv($booking->proposed_fare_minor * (int) $minPct + 99, 100),
            'max' => $maxPct === null ? null : intdiv($booking->proposed_fare_minor * (int) $maxPct, 100),
        ];
    }

    /**
     * Bookings created before the flag existed allow counter-offers (the category default).
     */
    public static function negotiationEnabled(Booking $booking): bool
    {
        return (bool) ($booking->policy_snapshot['offers']['negotiation_enabled'] ?? true);
    }
}
