<?php

namespace App\Enums\Notification;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Lifecycle of one attempt to reach one person on one channel.
 */
enum DeliveryStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function isFinal(): bool
    {
        return in_array($this, [self::Sent, self::Delivered, self::Failed, self::Skipped], true);
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Queued, self::Sending => BadgeColor::Info,
            self::Sent, self::Delivered => BadgeColor::Success,
            self::Failed => BadgeColor::Danger,
            self::Skipped => BadgeColor::Neutral,
        };
    }

    public function label(): string
    {
        return __("enums.delivery_status.{$this->value}");
    }
}
