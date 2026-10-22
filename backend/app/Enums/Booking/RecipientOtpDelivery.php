<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Outcome of sending the recipient code by SMS. `skipped` is a configured test number in a non-production environment.
 */
enum RecipientOtpDelivery: string implements HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __("enums.recipient_otp_delivery.{$this->value}");
    }
}
