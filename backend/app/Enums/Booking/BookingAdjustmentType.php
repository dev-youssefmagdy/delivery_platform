<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a post-selection fare adjustment is for (SRS §9.4). Toll / parking is charged at cost and always needs evidence;
 * waiting can be priced by the policy's per-minute rate; an extra stop or a scope change is negotiated.
 */
enum BookingAdjustmentType: string implements HasLabel
{
    use EnumHelpers;

    case ExtraStop = 'extra_stop';
    case Waiting = 'waiting';
    case TollParking = 'toll_parking';
    case ScopeChange = 'scope_change';
    case ExtraHelper = 'extra_helper';
    case Stairs = 'stairs';

    public function requiresEvidence(): bool
    {
        return $this === self::TollParking;
    }

    /**
     * Whether either party may ask (a customer cannot ask to pay more for waiting or a toll; the provider cannot ask to pay less).
     *
     * @return list<\App\Enums\Common\ActorType>
     */
    public function requesters(): array
    {
        return match ($this) {
            self::Waiting, self::TollParking => [\App\Enums\Common\ActorType::Provider],
            self::ExtraStop, self::ScopeChange, self::ExtraHelper, self::Stairs => [\App\Enums\Common\ActorType::Provider, \App\Enums\Common\ActorType::Customer],
        };
    }

    public function label(): string
    {
        return __("enums.booking_adjustment_type.{$this->value}");
    }
}
