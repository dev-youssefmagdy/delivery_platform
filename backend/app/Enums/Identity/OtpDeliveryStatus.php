<?php

namespace App\Enums\Identity;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum OtpDeliveryStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Sent = 'sent';
    case Failed = 'failed';

    /** A configured test number in local/testing: no SMS leaves the system. */
    case Skipped = 'skipped';

    public function label(): string
    {
        return __("enums.otp_delivery_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Sent => BadgeColor::Success,
            self::Failed => BadgeColor::Danger,
            self::Skipped => BadgeColor::Neutral,
        };
    }
}
