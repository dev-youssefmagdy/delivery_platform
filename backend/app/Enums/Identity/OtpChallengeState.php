<?php

namespace App\Enums\Identity;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Derived state of an OTP challenge (never stored): see OtpChallenge::state().
 */
enum OtpChallengeState: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Verified = 'verified';
    case Expired = 'expired';
    case Locked = 'locked';
    case Superseded = 'superseded';

    public function label(): string
    {
        return __("enums.otp_challenge_state.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Info,
            self::Verified => BadgeColor::Success,
            self::Expired, self::Superseded => BadgeColor::Neutral,
            self::Locked => BadgeColor::Danger,
        };
    }
}
