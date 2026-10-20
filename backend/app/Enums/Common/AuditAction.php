<?php

namespace App\Enums\Common;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Actions written to admin_audit_logs. Extend per module; never reuse a value for a different meaning.
 */
enum AuditAction: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case StatusChanged = 'status_changed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Reactivated = 'reactivated';
    case Published = 'published';
    case Exported = 'exported';
    case Viewed = 'viewed';
    case LoggedIn = 'logged_in';
    case LoggedOut = 'logged_out';
    case LoginFailed = 'login_failed';
    case Overridden = 'overridden';
    case MfaEnabled = 'mfa_enabled';
    case MfaDisabled = 'mfa_disabled';
    case MfaReset = 'mfa_reset';
    case PasswordChanged = 'password_changed';
    case SessionRevoked = 'session_revoked';
    case Restricted = 'restricted';
    case MessageSent = 'message_sent';
    case PhoneRevealed = 'phone_revealed';
    case Anonymised = 'anonymised';

    public function label(): string
    {
        return __("enums.audit_action.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Created, self::Approved, self::Reactivated, self::Restored, self::Published, self::LoggedIn => BadgeColor::Success,
            self::Deleted, self::Rejected, self::Suspended, self::LoginFailed, self::Anonymised => BadgeColor::Danger,
            self::Overridden, self::StatusChanged, self::MfaReset, self::MfaDisabled, self::Restricted, self::PhoneRevealed => BadgeColor::Warning,
            self::Updated, self::Exported, self::MfaEnabled, self::PasswordChanged, self::SessionRevoked, self::MessageSent => BadgeColor::Info,
            default => BadgeColor::Neutral,
        };
    }
}
