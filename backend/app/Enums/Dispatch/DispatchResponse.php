<?php

namespace App\Enums\Dispatch;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * What a notified provider did with a dispatched request (`booking_dispatch_recipients.response`).
 */
enum DispatchResponse: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Offered = 'offered';
    case Declined = 'declined';
    case Expired = 'expired';

    public function label(): string
    {
        return __("enums.dispatch_response.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Neutral,
            self::Offered => BadgeColor::Success,
            self::Declined => BadgeColor::Warning,
            self::Expired => BadgeColor::Danger,
        };
    }
}
