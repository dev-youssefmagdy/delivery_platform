<?php

namespace App\Enums\Notification;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Push campaign lifecycle: draft -> scheduled -> sending -> sent, or cancelled before it starts.
 */
enum CampaignStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Cancelled = 'cancelled';

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Scheduled], true);
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Scheduled => BadgeColor::Info,
            self::Sending => BadgeColor::Warning,
            self::Sent => BadgeColor::Success,
            self::Cancelled => BadgeColor::Danger,
        };
    }

    public function label(): string
    {
        return __("enums.campaign_status.{$this->value}");
    }
}
