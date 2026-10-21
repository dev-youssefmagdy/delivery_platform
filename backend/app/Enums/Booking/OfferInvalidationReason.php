<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Why the active offers of a booking are closed in bulk (maps to the offer statuses of SRS §8.2 in P2.4).
 */
enum OfferInvalidationReason: string implements HasLabel
{
    use EnumHelpers;

    case RequestChanged = 'request_changed';
    case SearchExpired = 'search_expired';
    case BookingCancelled = 'booking_cancelled';
    case ProviderUnavailable = 'provider_unavailable';

    /**
     * The final offer status this reason leads to.
     */
    public function offerStatus(): OfferStatus
    {
        return match ($this) {
            self::RequestChanged => OfferStatus::InvalidatedRequestChanged,
            self::SearchExpired, self::BookingCancelled => OfferStatus::Expired,
            self::ProviderUnavailable => OfferStatus::InvalidatedProviderUnavailable,
        };
    }

    public function label(): string
    {
        return __("enums.offer_invalidation_reason.{$this->value}");
    }
}
