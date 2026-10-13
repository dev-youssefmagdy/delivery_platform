<?php

namespace App\Enums\Common;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum HealthStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Ok = 'ok';
    case Degraded = 'degraded';
    case Failing = 'failing';
    case NotConfigured = 'not_configured';

    public function label(): string
    {
        return __("enums.health_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Ok => BadgeColor::Success,
            self::Degraded => BadgeColor::Warning,
            self::Failing => BadgeColor::Danger,
            self::NotConfigured => BadgeColor::Neutral,
        };
    }
}
