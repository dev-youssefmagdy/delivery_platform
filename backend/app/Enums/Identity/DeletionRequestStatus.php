<?php

namespace App\Enums\Identity;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum DeletionRequestStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function label(): string
    {
        return __("enums.deletion_request_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Warning,
            self::Cancelled => BadgeColor::Neutral,
            self::Rejected => BadgeColor::Danger,
            self::Completed => BadgeColor::Success,
        };
    }
}
