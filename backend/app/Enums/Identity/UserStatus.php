<?php

namespace App\Enums\Identity;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Lifecycle of a mobile account (SRS §6.2 step 8). Mirrored by the Flutter `UserStatus`.
 */
enum UserStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Active = 'active';
    case Restricted = 'restricted';
    case Suspended = 'suspended';
    case VerificationRequired = 'verification_required';
    case DeletionPending = 'deletion_pending';
    case Deleted = 'deleted';

    public function label(): string
    {
        return __("enums.user_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Success,
            self::Restricted, self::VerificationRequired, self::DeletionPending => BadgeColor::Warning,
            self::Suspended, self::Deleted => BadgeColor::Danger,
        };
    }

    /**
     * Stable API error code of the block this status puts on business endpoints, or null when it blocks nothing.
     * `restricted` blocks only where an endpoint asks for strict checking (for example creating a booking).
     */
    public function blockCode(bool $strict = false): ?string
    {
        return match ($this) {
            self::Active => null,
            self::Restricted => $strict ? 'account_restricted' : null,
            self::Suspended => 'account_suspended',
            self::VerificationRequired => 'account_verification_required',
            self::DeletionPending => 'account_deletion_pending',
            self::Deleted => 'account_deleted',
        };
    }
}
