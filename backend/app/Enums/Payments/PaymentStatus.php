<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Payment axis of a booking (Plan §7.1), separate from the trip lifecycle. P2.3 only writes `pending` (cash booking
 * not yet collected); the gateway and cash flows (P3.2) own the other transitions.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case NotRequired = 'not_required';
    case Pending = 'pending';
    case RequiresAction = 'requires_action';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case CashDue = 'cash_due';
    case CashCollected = 'cash_collected';
    case ReconciliationRequired = 'reconciliation_required';

    public function label(): string
    {
        return __("enums.payment_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::NotRequired => BadgeColor::Neutral,
            self::Pending, self::RequiresAction, self::Authorized, self::CashDue => BadgeColor::Info,
            self::Paid, self::CashCollected => BadgeColor::Success,
            self::Failed, self::ReconciliationRequired => BadgeColor::Danger,
            self::PartiallyRefunded, self::Refunded => BadgeColor::Warning,
        };
    }
}
