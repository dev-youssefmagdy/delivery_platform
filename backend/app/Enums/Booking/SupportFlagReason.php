<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Why a booking was flagged for support during a trip (`bookings.support_flag_reason`).
 */
enum SupportFlagReason: string implements HasLabel
{
    use EnumHelpers;

    case PinAttemptsExceeded = 'pin_attempts_exceeded';
    case RecipientOtpAttemptsExceeded = 'recipient_otp_attempts_exceeded';
    case DeliveryAttemptEscalated = 'delivery_attempt_escalated';

    public function label(): string
    {
        return __("enums.support_flag_reason.{$this->value}");
    }
}
