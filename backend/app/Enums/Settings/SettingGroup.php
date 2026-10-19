<?php

namespace App\Enums\Settings;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Settings screen tabs (one per group).
 */
enum SettingGroup: string implements HasLabel
{
    use EnumHelpers;

    case Localization = 'localization';
    case Support = 'support';
    case Otp = 'otp';
    case Booking = 'booking';
    case Dispatch = 'dispatch';
    case Providers = 'providers';
    case Safety = 'safety';
    case Apps = 'apps';
    case Security = 'security';
    case RateLimits = 'rate_limits';

    public function label(): string
    {
        return __("enums.setting_group.{$this->value}");
    }

    public function icon(): string
    {
        return match ($this) {
            self::Localization => 'map',
            self::Support => 'lifebuoy',
            self::Otp => 'lock-closed',
            self::Booking => 'clipboard',
            self::Dispatch => 'bolt',
            self::Providers => 'users',
            self::Safety => 'shield',
            self::Apps => 'cube',
            self::Security => 'key',
            self::RateLimits => 'clock',
        };
    }

    /**
     * @return list<SettingKey>
     */
    public function keys(): array
    {
        return array_values(array_filter(SettingKey::cases(), fn (SettingKey $key): bool => $key->group() === $this));
    }
}
