<?php

namespace App\Enums\Admin;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Result of one login step recorded in admin_login_events.
 */
enum AdminLoginOutcome: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Success = 'success';
    case InvalidCredentials = 'invalid_credentials';
    case AccountSuspended = 'account_suspended';
    case MfaFailed = 'mfa_failed';
    case Throttled = 'throttled';
    case Logout = 'logout';

    public function label(): string
    {
        return __("enums.admin_login_outcome.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Success => BadgeColor::Success,
            self::Logout => BadgeColor::Neutral,
            self::Throttled => BadgeColor::Warning,
            self::InvalidCredentials, self::AccountSuspended, self::MfaFailed => BadgeColor::Danger,
        };
    }

    public function isFailure(): bool
    {
        return ! in_array($this, [self::Success, self::Logout], true);
    }
}
