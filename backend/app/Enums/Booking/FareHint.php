<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Fairness hints returned next to an offer (SRS §9.3). They only inform; the server never changes a price because
 * of a hint.
 */
enum FareHint: string implements HasLabel
{
    use EnumHelpers;

    case BelowReference = 'below_reference_estimate';
    case AboveReference = 'above_reference_estimate';
    case BelowRecentOffers = 'below_recent_offers';
    case AboveRecentOffers = 'above_recent_offers';

    public function label(): string
    {
        return __("enums.fare_hint.{$this->value}");
    }
}
