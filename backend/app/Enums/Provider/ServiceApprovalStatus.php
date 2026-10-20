<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * State of one service category a provider asked to serve (`provider_service_category.status`).
 */
enum ServiceApprovalStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("enums.service_approval_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Requested => BadgeColor::Info,
            self::Approved => BadgeColor::Success,
            self::Rejected => BadgeColor::Danger,
        };
    }
}
