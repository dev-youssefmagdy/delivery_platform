<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How a case ended; chosen with a mandatory written reason.
 */
enum IncidentOutcome: string implements HasLabel
{
    use EnumHelpers;

    case ResolvedSafe = 'resolved_safe';
    case ActionTaken = 'action_taken';
    case ReferredToAuthorities = 'referred_to_authorities';
    case UnableToReach = 'unable_to_reach';
    case FalseAlarm = 'false_alarm';

    public function label(): string
    {
        return __("safety.enums.outcome.{$this->value}");
    }
}
