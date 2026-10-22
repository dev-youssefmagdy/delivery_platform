<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Why a hand-over at the drop-off did not happen (SRS §8.1 `delivery_attempt_failed`).
 */
enum DeliveryAttemptOutcome: string implements HasLabel
{
    use EnumHelpers;

    case RecipientUnavailable = 'recipient_unavailable';
    case RecipientRefused = 'recipient_refused';
    case WrongAddress = 'wrong_address';
    case OtpFailed = 'otp_failed';
    case RestrictedItem = 'restricted_item';
    case Other = 'other';

    /**
     * A free-text reason is mandatory for these.
     */
    public function needsReason(): bool
    {
        return $this === self::Other || $this === self::RestrictedItem;
    }

    public function label(): string
    {
        return __("enums.delivery_attempt_outcome.{$this->value}");
    }
}
