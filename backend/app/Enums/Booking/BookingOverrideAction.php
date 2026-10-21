<?php

namespace App\Enums\Booking;

use App\Enums\Admin\AdminPermission;
use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The operator interventions on a booking (SRS §8.3, §16.6). Each one needs its own permission, a reason, an audit
 * entry and a notice to the parties; none of them edits a historic offer or price, and a status change always goes
 * through `BookingTransitionService`.
 */
enum BookingOverrideAction: string implements HasLabel
{
    use EnumHelpers;

    case CancelByOperations = 'cancel_by_operations';
    case ExtendSearch = 'extend_search';
    case RedispatchNow = 'redispatch_now';
    case ForceTransition = 'force_transition';
    case LiftPinLock = 'lift_pin_lock';

    public function permission(): AdminPermission
    {
        return match ($this) {
            self::CancelByOperations => AdminPermission::BookingsCancel,
            self::ExtendSearch, self::RedispatchNow => AdminPermission::BookingsManage,
            self::ForceTransition => AdminPermission::BookingsAssignOverride,
            self::LiftPinLock => AdminPermission::BookingsPinOverride,
        };
    }

    public function label(): string
    {
        return __("enums.booking_override_action.{$this->value}");
    }
}
