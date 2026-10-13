<?php

namespace App\Enums\Support;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Semantic badge colours. The admin UI kit maps each value to Tailwind tokens.
 */
enum BadgeColor: string implements HasLabel
{
    use EnumHelpers;

    case Neutral = 'neutral';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
    case Primary = 'primary';

    public function label(): string
    {
        return __("enums.badge_color.{$this->value}");
    }
}
