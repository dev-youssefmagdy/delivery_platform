<?php

namespace App\Enums\Growth;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Weak fraud signals. Each one only holds a reward for manual review; none of them is ever a punishment.
 */
enum FraudFlag: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case SameDevice = 'same_device';
    case SamePhonePattern = 'same_phone_pattern';
    case SamePaymentCard = 'same_payment_card';
    case Velocity = 'velocity';
    case LinkedAccount = 'linked_account';
    case CustomerRepeat = 'customer_repeat';
    case RiskSignalOpen = 'risk_signal_open';

    public function label(): string
    {
        return __("growth.enums.fraud_flag.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::SameDevice => BadgeColor::Warning,
            self::SamePhonePattern => BadgeColor::Warning,
            self::SamePaymentCard => BadgeColor::Warning,
            self::Velocity => BadgeColor::Warning,
            self::LinkedAccount => BadgeColor::Warning,
            self::CustomerRepeat => BadgeColor::Warning,
            self::RiskSignalOpen => BadgeColor::Warning,
        };
    }

}
