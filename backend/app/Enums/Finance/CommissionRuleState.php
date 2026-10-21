<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;
use App\Models\CommissionRule;
use Carbon\CarbonInterface;

/**
 * Where a commission rule stands now: derived from its enabled flag and period, never stored.
 */
enum CommissionRuleState: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Current = 'current';
    case Scheduled = 'scheduled';
    case Expired = 'expired';
    case Inactive = 'inactive';

    public static function of(CommissionRule $rule, ?CarbonInterface $at = null): self
    {
        $at ??= now();

        return match (true) {
            ! $rule->active => self::Inactive,
            $rule->effective_from->greaterThan($at) => self::Scheduled,
            $rule->effective_to !== null && $rule->effective_to->lessThanOrEqualTo($at) => self::Expired,
            default => self::Current,
        };
    }

    public function label(): string
    {
        return __("enums.commission_rule_state.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Current => BadgeColor::Success,
            self::Scheduled => BadgeColor::Info,
            self::Expired, self::Inactive => BadgeColor::Neutral,
        };
    }
}
