<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Review state of an uploaded provider or vehicle document. `expired` is set by the daily expiry check
 * (an approved document whose expiry date passed).
 */
enum DocumentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return __("enums.document_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Info,
            self::Approved => BadgeColor::Success,
            self::Rejected => BadgeColor::Danger,
            self::Expired => BadgeColor::Warning,
        };
    }
}
