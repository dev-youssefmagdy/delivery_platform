<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;
use App\Models\CancellationPolicy;
use Carbon\CarbonInterface;

/**
 * Where a cancellation policy stands now: derived from its enabled flag and period, never stored.
 */
enum CancellationPolicyState: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Current = 'current';
    case Scheduled = 'scheduled';
    case Expired = 'expired';
    case Inactive = 'inactive';

    public static function of(CancellationPolicy $policy, ?CarbonInterface $at = null): self
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
        return __("enums.cancellation_policy_state.{$this->value}");
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
