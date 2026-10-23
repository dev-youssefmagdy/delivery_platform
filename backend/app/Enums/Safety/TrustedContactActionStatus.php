<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum TrustedContactActionStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __("safety.enums.contact_action_status.{$this->value}");
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
