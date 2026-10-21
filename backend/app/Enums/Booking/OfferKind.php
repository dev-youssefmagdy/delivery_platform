<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Whether the provider took the customer's fare as it is or proposed another one.
 */
enum OfferKind: string implements HasLabel
{
    use EnumHelpers;

    case AcceptCustomerFare = 'accept_customer_fare';
    case CounterOffer = 'counter_offer';

    public function activeStatus(): OfferStatus
    {
        return $this === self::AcceptCustomerFare ? OfferStatus::Submitted : OfferStatus::CounterOffered;
    }

    public function label(): string
    {
        return __("enums.offer_kind.{$this->value}");
    }
}
