<?php

namespace App\Enums\Pricing;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;
use App\Models\FarePolicy;
use Carbon\CarbonInterface;

/**
 * Where a fare policy stands now: derived from its enabled flag and period, never stored.
 */
enum FarePolicyState: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Current = 'current';
    case Scheduled = 'scheduled';
    case Expired = 'expired';
    case Inactive = 'inactive';

    public static function of(FarePolicy $policy, ?CarbonInterface $at = null): self
    {
        $at ??= now();

        return match (true) {
            ! $policy->active => self::Inactive,
            $policy->effective_from->greaterThan($at) => self::Scheduled,
            $policy->effective_to !== null && $policy->effective_to->lessThanOrEqualTo($at) => self::Expired,
            default => self::Current,
        };
    }

    public function label(): string
    {
        return __("enums.fare_policy_state.{$this->value}");
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
