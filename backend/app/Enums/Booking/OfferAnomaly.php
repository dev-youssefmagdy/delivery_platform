<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Review flag of an offer (SRS §16.7): far below or far above the reference estimate stored on its booking. A flag
 * never edits or blocks the offer.
 */
enum OfferAnomaly: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case VeryLow = 'very_low';
    case VeryHigh = 'very_high';

    public function label(): string
    {
        return __("enums.offer_anomaly.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return $this === self::VeryLow ? BadgeColor::Warning : BadgeColor::Danger;
    }
}
