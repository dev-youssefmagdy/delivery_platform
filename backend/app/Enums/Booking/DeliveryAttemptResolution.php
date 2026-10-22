<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What happens after a failed delivery attempt: another attempt, the parcel goes back to the sender, or operations decide.
 */
enum DeliveryAttemptResolution: string implements HasLabel
{
    use EnumHelpers;

    case Retry = 'retry';
    case ReturnToSender = 'return_to_sender';
    case Operations = 'operations';

    public function label(): string
    {
        return __("enums.delivery_attempt_resolution.{$this->value}");
    }
}
