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

    public function label(): string
    {
        return __("enums.audit_action.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Created, self::Approved, self::Reactivated, self::Restored, self::Published, self::LoggedIn => BadgeColor::Success,
            self::Deleted, self::Rejected, self::Suspended, self::LoginFailed => BadgeColor::Danger,
            self::Overridden, self::StatusChanged => BadgeColor::Warning,
            self::Updated, self::Exported => BadgeColor::Info,
            default => BadgeColor::Neutral,
        };
    }
}
